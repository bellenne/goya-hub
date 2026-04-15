# Goy Table

Laravel pet-project for a tabletop RPG web app with Game Master and Player interfaces.

## Stack

- Laravel 13
- PostgreSQL
- Redis
- Laravel Reverb
- Vue 3 + Inertia
- Vite
- Tailwind CSS
- Docker Compose

## Services

- `app` - PHP 8.3, Composer, Node.js
- `postgres` - PostgreSQL 16
- `redis` - Redis 7

## Project start

1. Build and start containers:

```bash
docker compose up -d --build
```

2. Install Composer dependencies:

```bash
docker compose exec app composer install
```

3. Install NPM dependencies:

```bash
docker compose exec app npm install
```

4. Generate app key:

```bash
docker compose exec app php artisan key:generate
```

5. Run migrations:

```bash
docker compose exec app php artisan migrate
```

6. Prepare public storage:

```bash
docker compose exec app php artisan storage:link
```

7. Build frontend:

```bash
docker compose exec app npm run build
```

## Development

Run Vite dev server:

```bash
docker compose exec app npm run dev
```

Run tests:

```bash
docker compose exec app php artisan test
```

Start Reverb manually if needed:

```bash
docker compose exec app php artisan reverb:start --host=0.0.0.0 --port=8080
```

## URLs

- App: `http://localhost:8000`
- Vite: `http://localhost:5173`
- Reverb: `ws://localhost:8080`

## Host configuration

All external hosts are configured through `.env`:

- `APP_URL` - backend URL
- `VITE_DEV_SERVER_HMR_HOST` - host/domain for Vite HMR
- `REVERB_HOST` / `VITE_REVERB_HOST` - websocket host/domain

For production or remote dev, you only need to change values in `.env` instead of editing source files.
