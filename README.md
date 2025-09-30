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

### API endpoints (summary)

- Auth: `POST /api/register`, `POST /api/login`, `GET /api/user`, `POST /api/logout`
- 2FA (TOTP): `POST /api/2fa/setup`, `POST /api/2fa/enable`, `POST /api/2fa/disable`
- Magic link: `POST /api/magic`, `GET /api/magic/consume/{token}`
- Analytics: `GET /api/users/top-logins`, `GET /api/users/inactive`

- OpenAPI spec file: `public/openapi.yaml` → `http://localhost:9000/openapi.yaml`
 - Swagger UI: `http://localhost:9000/docs`

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


