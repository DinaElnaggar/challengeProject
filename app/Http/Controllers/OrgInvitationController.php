<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrgInvitationController extends Controller
{
    public function invite(Request $request, int $org)
    {
        Gate::authorize('users.invite', $org);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role' => 'nullable|in:owner,admin,member,auditor',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => ['message' => 'Validation failed', 'details' => $validator->errors()->toArray()]], 422);
        }

        $token = Str::random(40);
        Invitation::create([
            'organization_id' => $org,
            'email' => $request->input('email'),
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        // Send users to the public landing page instead of API to avoid auth redirects
        $url = url("/invitations/accept?token={$token}");
        \Mail::raw("You have been invited to join organization #{$org}. Click to accept: {$url}", function ($message) use ($request) {
            $message->to($request->input('email'))->subject('Organization Invitation');
        });

        return response()->json(['success' => true, 'data' => ['message' => 'Invitation sent']]);
    }

    public function accept(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'sometimes|email',
        ]);
        $invite = Invitation::where('token', $request->input('token'))->first();
        if (! $invite || ($invite->expires_at && $invite->expires_at->isPast())) {
            return response()->json(['success' => false, 'error' => ['message' => 'Invalid or expired token']], 400);
        }

        $user = auth('api')->user();
        if (! $user) {
            // Allow acceptance by matching the invitation email to an existing user when email is provided
            $email = $request->input('email') ?: $invite->email;
            $user = User::where('email', $email)->first();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'error' => ['message' => 'Unauthorized. Please log in or register with the invited email.']
                ], 401);
            }
        }

        $roleName = $request->input('role', 'member');
        $roleId = \DB::table('roles')->where('name', $roleName)->value('id');
        if (! $roleId) {
            $roleId = \DB::table('roles')->where('name', 'member')->value('id');
        }

        \DB::table('organization_user_roles')->updateOrInsert(
            ['organization_id' => $invite->organization_id, 'user_id' => $user->id],
            ['role_id' => $roleId]
        );

        $invite->accepted_at = now();
        $invite->save();

        return response()->json(['success' => true, 'data' => ['message' => 'Joined organization']]);
    }

    /**
     * Public landing page for invitation acceptance via email link.
     * Shows next steps when not authenticated.
     */
    public function acceptPage(Request $request)
    {
        $token = $request->query('token');
        if (! $token) {
            return response()->view('invitations.accept', [
                'status' => 'error',
                'message' => 'Missing invitation token.',
                'token' => null,
            ]);
        }

        $invite = Invitation::where('token', $token)->first();
        if (! $invite || ($invite->expires_at && $invite->expires_at->isPast())) {
            return response()->view('invitations.accept', [
                'status' => 'error',
                'message' => 'This invitation link is invalid or has expired.',
                'token' => $token,
            ]);
        }

        $user = auth('api')->user();
        // If already authenticated in API context, we can suggest hitting the API accept endpoint.
        $isAuthenticated = (bool) $user;

        return response()->view('invitations.accept', [
            'status' => 'ok',
            'message' => null,
            'token' => $token,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }
}

