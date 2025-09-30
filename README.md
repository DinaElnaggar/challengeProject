## Orthoplex API (Laravel)

Concise guide for running the stack and using the core features: JWT auth, email verification, optional 2FA (TOTP), passwordless magic links, and basic login analytics.

### Quick start

1) Start services

```bash
docker compose up -d --build
```

App base URL: `http://localhost:9000`

2) Install dependencies (if not already installed in the container)

```bash
docker compose exec -T laravel composer install
```

3) Environment setup

- Copy `.env.example` to `.env` and set DB credentials to match `compose.yaml`.
- Generate keys/secrets:

```bash
docker compose exec -T laravel php artisan key:generate -n
docker compose exec -T laravel php artisan jwt:secret -n
```

4) Run migrations

```bash
docker compose exec -T laravel php artisan migrate
```

5) Run seeders

```bash
docker compose exec -T laravel php artisan db:seed
```

Alternatively, to migrate and seed in one step:

```bash
docker compose exec -T laravel php artisan migrate --seed
```

6) (Optional) Start queue worker for async jobs

```bash
docker compose exec -d laravel php artisan queue:work --queue=default --sleep=1 --tries=3
```

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
MAIL_FROM_NAME=Orthoplex
```

### API endpoints (summary)

- Auth: `POST /api/register`, `POST /api/login`, `GET /api/user`, `POST /api/logout`
- 2FA (TOTP): `POST /api/2fa/setup`, `POST /api/2fa/enable`, `POST /api/2fa/disable`
- Magic link: `POST /api/magic`, `GET /api/magic/consume/{token}`
- Analytics: `GET /api/users/top-logins`, `GET /api/users/inactive`

All protected routes require `Authorization: Bearer <JWT>`.

### Troubleshooting

- No analytics data: make sure the queue worker is running and users have logged in.
- Organization context: pass `organization_id` on login for multi‑org users.
- If email verification is required, use Mailpit to open the verification link.

### License

MIT
