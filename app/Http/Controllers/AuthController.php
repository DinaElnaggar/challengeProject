<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use App\Models\MagicLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Concerns\ApiResponses;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Jobs\RecordLoginEvent;
use Illuminate\Support\Facades\DB;

/**
 * Authentication API controller handling registration, login, email verification,
 * two-factor authentication (TOTP), magic link login, and logout.
 */
class AuthController extends Controller
{
    use ApiResponses;

    /**
     * Register a new user and send an email verification link.
     *
     * Body: { name, email, password }
     * Optional header: Idempotency-Key
     */
    public function register(\App\Http\Requests\RegisterRequest $request)
    {
        $this->assertIdempotency($request);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        // Do not log in until email verified
        return $this->idempotentResponse($request, [
            'message' => 'Registered. Please verify your email before login.',
        ], 201);
    }

    /**
     * Login with email and password. If 2FA enabled, require otp or backup_code.
     * Optional header: Idempotency-Key
     */
    public function login(\App\Http\Requests\LoginRequest $request)
    {
        $this->assertIdempotency($request);
        $this->ensureIsNotRateLimited($request);

        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            RateLimiter::hit($this->throttleKey($request));
            return $this->unauthorized('Invalid credentials');
        }

        $user = auth('api')->user();
        if (! $user || ! $user->hasVerifiedEmail()) {
            auth('api')->logout();
            return $this->forbidden('Email not verified');
        }

        // If 2FA is enabled, require valid OTP before issuing token
        if ($user->two_factor_enabled) {
            $otp = $request->input('otp');
            $backup = $request->input('backup_code');
            $verified = false;

            if ($otp) {
                $google2fa = new Google2FA();
                $verified = $google2fa->verifyKey((string) $user->two_factor_secret, (string) $otp);
            } elseif ($backup) {
                $codes = (array) json_decode((string) $user->two_factor_backup_codes, true) ?: [];
                if (in_array($backup, $codes, true)) {
                    $verified = true;
                    // consume backup code
                    $codes = array_values(array_diff($codes, [$backup]));
                    $user->two_factor_backup_codes = json_encode($codes);
                    $user->save();
                }
            }

            if (! $verified) {
                auth('api')->logout();
                return $this->unauthorized('OTP required');
            }
        }

        RateLimiter::clear($this->throttleKey($request));

        // Determine organization to attribute the login to
        $orgId = $request->integer('organization_id');
        $memberships = DB::table('organization_user_roles')
            ->where('user_id', $user->id)
            ->pluck('organization_id');

        if (! $orgId) {
            if ($memberships->count() === 1) {
                $orgId = (int) $memberships->first();
            } elseif ($memberships->count() > 1) {
                return $this->validationError([
                    'organization_id' => ['organization_id is required for users in multiple organizations']
                ]);
            }
        } else {
            // Validate provided org belongs to the user
            if (! $memberships->contains((int) $orgId)) {
                return $this->forbidden('You are not a member of this organization');
            }
        }

        // Queue login event recording and transactional user stats update
        RecordLoginEvent::dispatch(
            userId: $user->id,
            organizationId: $orgId ?: null,
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent(),
            loggedInAt: now()->toDateTimeString(),
        )->onQueue('default');

        return $this->idempotentResponse($request, [
            'message' => 'Login successful',
            'token' => $token,
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function user()
    {
        return $this->success(['user' => auth('api')->user()]);
    }

    /**
     * Logout the current user by invalidating their token.
     */
    public function logout()
    {
        auth('api')->logout();

        return $this->success(['message' => 'Successfully logged out']);
    }

    /**
     * Send a passwordless magic login link to a verified user.
     */
    public function magicLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return $this->success(['message' => 'If the email exists, a link will be sent']);
        }

        if (! $user->hasVerifiedEmail()) {
            return $this->forbidden('Email not verified');
        }

        $token = Str::random(64);
        $link = MagicLink::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addMinutes(15),
        ]);

        $url = url("/api/magic/consume/{$token}");
        $user->notify(new \Illuminate\Auth\Notifications\VerifyEmail()); // placeholder ensure mail works
        \Mail::raw("Login link: {$url}", function ($message) use ($user) {
            $message->to($user->email)->subject('Your magic login link');
        });

        return $this->success(['message' => 'Magic link sent']);
    }

    /**
     * Consume a magic link token; one-time use and time-limited.
     */
    public function magicConsume(string $token)
    {
        $link = MagicLink::where('token', $token)->first();
        if (! $link || $link->consumed_at || $link->expires_at->isPast()) {
            return $this->fail('Invalid or expired link', [], 400);
        }

        $user = $link->user;
        if (! $user || ! $user->hasVerifiedEmail()) {
            return $this->forbidden('Email not verified');
        }

        $link->consumed_at = now();
        $link->save();

        $token = auth('api')->login($user);

        return $this->success(['message' => 'Login successful', 'token' => $token, 'user' => $user]);
    }

    /**
     * Verify the user's email via signed URL (no JWT auth required).
     */
    public function verifySigned(Request $request, string $id, string $hash)
    {
        $user = User::find($id);
        if (! $user) {
            return $this->notFound('User not found');
        }

        // Validate the hash matches the user's email
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->forbidden('Invalid verification link');
        }

        if ($user->hasVerifiedEmail()) {
            return view('verified');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return view('verified');
    }

    /**
     * Resend email verification link to a user by email.
     */
    public function resendVerification(HttpRequest $request)
    {
        $this->ensureIsNotRateLimited($request, attempts: 5, decaySeconds: 60);

        $user = User::where('email', $request->input('email'))
            ->first();

        if (! $user) {
            return $this->success(['message' => 'If your email exists, a link will be sent.']);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success(['message' => 'Email already verified']);
        }

        $user->sendEmailVerificationNotification();

        return $this->success(['message' => 'Verification link sent']);
    }

    private function ensureIsNotRateLimited(HttpRequest $request, int $attempts = 5, int $decaySeconds = 60): void
    {
        $key = $this->throttleKey($request);
        if (! RateLimiter::tooManyAttempts($key, $attempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);
        abort($this->tooManyRequests($seconds));
    }

    private function throttleKey(HttpRequest $request): string
    {
        return Str::lower($request->input('email', 'guest')).'|'.$request->ip();
    }

    private function assertIdempotency(Request $request): void
    {
        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return; // optional: enforce by aborting if missing
        }

        $cacheKey = $this->buildIdempotencyCacheKey($request, $key);
        if (Cache::has($cacheKey)) {
            $stored = Cache::get($cacheKey);
            throw new HttpResponseException(response()->json($stored['body'], $stored['status']));
        }
    }

    private function idempotentResponse(Request $request, array $body, int $status = 200)
    {
        $key = $request->header('Idempotency-Key');
        if ($key) {
            $cacheKey = $this->buildIdempotencyCacheKey($request, $key);
            Cache::put($cacheKey, ['body' => $body, 'status' => $status], now()->addMinutes(10));
        }
        return response()->json($body, $status);
    }

    private function buildIdempotencyCacheKey(Request $request, string $key): string
    {
        $fingerprint = sha1($request->method().'|'.$request->fullUrl().'|'.json_encode($request->all()));
        return 'idem:'.$key.':'.$fingerprint;
    }

    /**
     * Generate and persist a new TOTP secret; returns otpauth URL for QR code.
     */
    public function twoFactorSetup()
    {
        $user = auth('api')->user();
        if (! $user) {
            return $this->unauthorized();
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $user->two_factor_secret = $secret;
        $user->save();

        $otpauth = $google2fa->getQRCodeUrl('Orthoplex', (string) $user->email, $secret);

        return $this->success(['secret' => $secret, 'otpauth_url' => $otpauth]);
    }

    /**
     * Confirm OTP to enable 2FA and issue backup codes.
     */
    public function twoFactorEnable(Request $request)
    {
        $user = auth('api')->user();
        if (! $user) {
            return $this->unauthorized();
        }

        $request->validate(['otp' => 'required|string']);
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey((string) $user->two_factor_secret, (string) $request->input('otp'));
        if (! $valid) {
            return $this->validationError(['otp' => ['Invalid OTP']]);
        }

        $user->two_factor_enabled = true;
        // generate 8 backup codes
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(10);
        }
        $user->two_factor_backup_codes = json_encode($codes);
        $user->save();

        return $this->success(['message' => '2FA enabled', 'backup_codes' => $codes]);
    }

    /**
     * Disable 2FA for the authenticated user.
     */
    public function twoFactorDisable()
    {
        $user = auth('api')->user();
        if (! $user) {
            return $this->unauthorized();
        }

        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_backup_codes = null;
        $user->save();

        return $this->success(['message' => '2FA disabled']);
    }
}

