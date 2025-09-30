<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Project Setup and JWT API Authentication Steps

The following steps outline how this project is containerized and how JWT-based API authentication was added.

### 1. Run the stack with Docker

- Ensure Docker is installed and running.
- From the project root, start the services:

```bash
docker compose up -d --build
```

- The app serves on port 9000 inside the container and is exposed on `http://localhost:9000`.

### 2. Install dependencies inside the container

Run Composer install on container startup is already defined in the `Dockerfile`. If you need to run it manually:

```bash
docker compose exec -T laravel composer install
```

Run database migrations (configure `.env` first if needed):

```bash
docker compose exec -T laravel php artisan migrate --force
``;

### 3. Install and configure JWT (tymon/jwt-auth)

Installed package and published config:

```bash
docker compose exec -T laravel sh -lc "composer require tymon/jwt-auth:^2.0 --no-interaction && php artisan vendor:publish --provider=\"Tymon\\JWTAuth\\Providers\\LaravelServiceProvider\" --force"
```

Generate `JWT_SECRET` in `.env`:

```bash
docker compose exec -T laravel php artisan jwt:secret -n
```

Key files updated/added:
- `config/jwt.php` (published)
- `.env` received `JWT_SECRET` (generated)

### 4. Configure authentication guard

Updated `config/auth.php` to use the JWT driver for the `api` guard and set default guard to `api`:

```12:22:config/auth.php
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'api'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],
```

```36:47:config/auth.php
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],
```

### 5. Implement JWT in the User model

Implemented `JWTSubject` on `App\Models\User`:

```1:20:app/Models/User.php
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
```

### 6. Add AuthController

Created `app/Http/Controllers/AuthController.php` with `register`, `login`, `user`, and `logout` endpoints. It uses `Auth::login`, `Auth::attempt`, and `Auth::logout` with JWT guard.

### 7. Define API routes

Created `routes/api.php`:

```1:12:routes/api.php
use App\Http\Controllers\AuthController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
});
```

### 8. Test the API

Base URL: `http://localhost:9000/api`

- Register: `POST /register`

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

- Login: `POST /login`

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

- Get current user: `GET /user` with header `Authorization: Bearer <token>`
- Logout: `POST /logout` with header `Authorization: Bearer <token>`

### 9. Email verification, throttling, MailHog

- App is served by PHP built-in server inside the container (`:9000`).
- Update database credentials in `.env` to match `compose.yaml` (service `mysql`).
- If you prefer sessions for web and JWT only for API, set `defaults.guard` back to `web` and keep `auth:api` on API routes.
- Email verification is required. Use `POST /api/email/resend` then verify at the link in MailHog (`http://localhost:8025`).
- Login throttled: `throttle:login` (5/min). Resend throttled: `throttle:email` (3/min).

### 10. Two-Factor Authentication (TOTP) with backup codes

- Enable flow (requires authenticated user):
  - `POST /api/2fa/setup` → returns `secret` and `otpauth_url` to scan.
  - `POST /api/2fa/enable` with `{ "otp": "123456" }` → returns `backup_codes`.
- Disable: `POST /api/2fa/disable`.
- Login when 2FA enabled: include either `otp` or `backup_code` fields in `POST /api/login` body.

### 11. Passwordless Login (Magic Link)

- Request link: `POST /api/magic` with `{ "email": "user@example.com" }`.
- Open MailHog (`http://localhost:8025`) and click the link to `/api/magic/consume/{token}`.
- Links expire after 15 minutes and are one-time use.

### 12. Idempotency for Register/Login

- Send header `Idempotency-Key: <unique-key>` on `POST /api/register` and `POST /api/login`.
- The first response is cached for 10 minutes and returned for duplicate requests with the same key.

### 13. Mail service (Mailpit)

We initially used MailHog, but its web UI did not render correctly on this machine (likely UI script issues on Apple Silicon). Mailpit is a drop-in replacement with a modern, reliable UI and compatible SMTP port mapping, so we swapped to ensure a stable developer experience.

Set the following in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME=Orthoplex
```

Compose service now runs `axllent/mailpit:latest` on ports 8025 (UI) and 1025 (SMTP). Open `http://localhost:8025` to view captured emails.

## Login Analytics

Tracks every successful login and maintains daily aggregates per user and organization. Provides endpoints to fetch top users by logins and list inactive users within an organization.

### Data Model
- `users`
  - Added: `last_login_at` (timestamp, nullable), `login_count` (bigint, default 0)
- `login_events`
  - Columns: `id`, `user_id`, `organization_id` (nullable), `ip_address`, `user_agent`, `logged_in_at`, timestamps
  - Indexes: `(organization_id, logged_in_at)`, `(user_id, logged_in_at)`
- `login_daily`
  - Columns: `id`, `user_id`, `organization_id` (nullable), `date`, `count`, timestamps
  - Constraints: unique `(user_id, organization_id, date)`; index `(organization_id, date)`

### Event Flow
1. Successful login (`POST /api/login`) triggers queued job `RecordLoginEvent`.
2. Job writes `login_events` row and updates `users.last_login_at` and `users.login_count` transactionally.
3. Nightly command `analytics:rollup-login-daily` aggregates counts per user/org/date into `login_daily`.

### Organization Attribution on Login
- `organization_id` may be passed to `POST /api/login`.
- If user belongs to exactly one organization, it is auto-selected if omitted.
- If user belongs to multiple organizations, `organization_id` is required and must match a membership. Otherwise a validation error is returned.

### Endpoints
- GET `/api/users/top-logins?org_id={id}&window=7d|30d`
  - Auth: `auth:api`
  - Params: `org_id` required; `window` optional (`7d` default, or `30d`).
  - Uses `login_daily` sums over the window; falls back to `login_events` if aggregates are empty.
  - Response: `{ "top": [ { "user_id", "name", "email", "logins" }, ... ] }`

- GET `/api/users/inactive?org_id={id}&window=hour|day|week|month&cursor={cursor}`
  - Auth: `auth:api`
  - Lists org members where `users.last_login_at` is null or older than the threshold.
  - Cursor-paginated (25/page). Response includes `next_cursor`/`prev_cursor`.

### Setup
1. Migrate database:
```bash
docker compose exec -T laravel php artisan migrate --force
```
2. Start a queue worker (for `RecordLoginEvent`):
```bash
docker compose exec -d laravel php artisan queue:work --queue=default --sleep=1 --tries=3
```
3. Scheduler (cron) should run Laravel every minute so the nightly rollup runs at 01:00:
```bash
* * * * * php /var/www/html/artisan schedule:run >> /dev/null 2>&1
```
4. Manual rollup (useful in testing):
```bash
docker compose exec -T laravel php artisan analytics:rollup-login-daily --date=$(date +%F)
```

### Usage Examples
- Login (single-org user; org inferred):
```bash
curl -X POST http://localhost:9000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret"}'
```
- Login (multi-org user; org required):
```bash
curl -X POST http://localhost:9000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret","organization_id":1}'
```
- Top logins (7 days):
```bash
curl -H "Authorization: Bearer <JWT>" \
  "http://localhost:9000/api/users/top-logins?org_id=1&window=7d"
```
- Inactive users (week):
```bash
curl -H "Authorization: Bearer <JWT>" \
  "http://localhost:9000/api/users/inactive?org_id=1&window=week"
```

### Troubleshooting
- Organization id is null in events:
  - For multi-org users, pass `organization_id` when logging in.
  - For single-org users, it is inferred if omitted.
- No data in top-logins:
  - Ensure queue worker is running and logins have occurred.
  - Run the rollup for the relevant date if testing same-day.
- Inactive endpoint shows many users:
  - `last_login_at` updates via the job; ensure the queue is processing.
