## Challenge API (Laravel)

Concise guide for running the stack and using the core features: JWT auth, email verification, optional 2FA (TOTP), passwordless magic links, and basic login analytics.

### Tech Stack

- Laravel 11 (Framework)
- MySQL (Database)
- Redis (Cache & queues)
- Mailpit/Mailhog (Email testing)
- Swagger (OpenAPI) (API docs)
- JWT (Auth)
- Google2FA (TOTP)

### Quick start

1) Start services

```bash
docker compose up -d --build
```

App: `http://localhost:9000`  •  API Docs: `http://localhost:9000/docs`

The container entrypoint will automatically:
- install Composer dependencies
- generate `APP_KEY` if missing
- generate `JWT_SECRET` if missing
- run `php artisan migrate --seed` (retries until DB is ready)
- start a background queue worker (disable with `START_QUEUE_WORKER=0`)

### Mail (Mailpit)

- UI: `http://localhost:8025`
- SMTP: host `mailhog`, port `1025`
- Example `.env` mail settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME=Challenge Company
```

### API Documentation

**Complete API Reference:** [Swagger UI](http://localhost:9000/docs) | [`openapi.yaml`](public/openapi.yaml)

#### Core Endpoints

**Authentication & Authorization**
- `POST /api/register` - User registration with email verification
- `POST /api/login` - JWT-based authentication with optional 2FA
- `GET /api/user` - Get current authenticated user profile
- `POST /api/logout` - Invalidate JWT token

**Two-Factor Authentication (TOTP)**
- `POST /api/2fa/setup` - Generate TOTP secret and QR code
- `POST /api/2fa/enable` - Enable 2FA with OTP verification
- `POST /api/2fa/disable` - Disable 2FA for current user

**Passwordless Authentication**
- `POST /api/magic` - Request magic login link via email
- `GET /api/magic/consume/{token}` - Consume magic link token

**User Management & Analytics**
- `GET /api/users` - List users (paginated)
- `POST /api/users` - Create new user
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user information
- `DELETE /api/users/{id}` - Soft delete user
- `POST /api/users/{id}/restore` - Restore deleted user
- `GET /api/users/top-logins` - Top users by login activity
- `GET /api/users/inactive` - Inactive users report

**GDPR Compliance**
- `POST /api/users/{id}/export` - Request user data export
- `GET /api/users/{id}/export/download` - Download export archive
- `POST /api/users/{id}/gdpr-delete` - Request user data deletion
- `POST /api/gdpr-delete/{id}/approve` - Approve deletion request
- `POST /api/gdpr-delete/{id}/reject` - Reject deletion request
- `POST /api/gdpr-delete/{id}/process` - Process approved deletion


All protected routes require `Authorization: Bearer <JWT>`.

### Optional manual commands

If you prefer to run steps manually inside the container:

```bash
docker compose exec -T laravel composer install
docker compose exec -T laravel php artisan key:generate -n
docker compose exec -T laravel php artisan jwt:secret -n
docker compose exec -T laravel php artisan migrate --seed
docker compose exec -d laravel php artisan queue:work --queue=default --sleep=1 --tries=3
```

### Troubleshooting

- No analytics data: make sure the queue worker is running and users have logged in.
- Organization context: pass `organization_id` on login for multi‑org users.
- If email verification is required, use Mailpit to open the verification link.


