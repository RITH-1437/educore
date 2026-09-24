# EduCore

University Digital Administration Platform (Cambodian Universities).

- **Primary Stack:** Laravel (backend) + Vue.js (frontend)
- **Database:** PostgreSQL
- **Storage:** MinIO (S3-compatible) for object storage
- **Cache/Queue/Session:** Redis
- **Deployment:** Docker + Laravel Cloud

Full product report: [`docs/1_EduCore-Project-Report.md`](docs/1_EduCore-Project-Report.md)

---

# Docker Development

A production-quality local environment that mirrors the production topology:
Nginx as the single entry point, Laravel PHP-FPM behind it, Vue via Vite dev
server (with HMR), plus PostgreSQL, pgAdmin, Redis, and MinIO.

## 1. Prerequisites

- **Docker Desktop** (or Docker Engine) with the Docker Compose plugin installed.
- **No local PHP/Node toolchain required** — everything runs inside containers.
- Ports must be free (see #14 for the full port table): `80` (nginx), `5433`
  (PostgreSQL), `5051` (pgAdmin), `6380` (Redis), `9100`/`9101` (MinIO), `5173`
  (Vite, optional). If your machine is already using any of them, adjust the
  matching `*_PORT` value in `.env` and regenerate the URLs below.

## 2. Environment setup

All configuration lives in a single root-level `.env` file. It is git-ignored
and never committed. The template is `.env.docker.example`.

Variables group by responsibility:

- `APP_*` — Laravel application settings.
- `POSTGRES_*` / `PGADMIN_*` — database service credentials and host ports.
- `REDIS_*` — Redis host ports.
- `MINIO_*` / `AWS_*` — object storage credentials, bucket name, API/console ports.
- `NGINX_PORT` / `FRONTEND_PORT` — host ports for the web entry point.
- `VITE_API_URL` / `VITE_API_PROXY_TARGET` — frontend API base path and proxy target.

> Note: `APP_KEY` is intentionally omitted. It is auto-generated into
> `backend/.env` on the first container start and persists thereafter.

## 3. Copy `.env.docker.example` to `.env`

```sh
# Windows (PowerShell)
copy .env.docker.example .env

# macOS / Linux
cp .env.docker.example .env
```

Adjust the placeholder credentials (PostgreSQL password, MinIO keys, pgAdmin
login) to your liking before the first `up`.

## 4. Start the containers

```sh
docker compose up -d
```

First run builds the `backend` and `frontend` images, installs Composer
dependencies and `npm` dependencies inside the containers, creates the MinIO
bucket, and starts everything. Subsequent starts are fast.

## 5. Check status

```sh
docker compose ps
```

All services should show `healthy` (`/healthy` for minio-init). See `make ps`.

## 6. Run database migrations

```sh
docker compose exec backend php artisan migrate
```

Or via Make: `make migrate`.

## 7. Seed the database

```sh
docker compose exec backend php artisan db:seed
```

Or via Make: `make seed`. To drop and re-seed in one step: `make migrate-fresh`.

## 8. Access the application

| URL                          | Purpose                                    |
| ---------------------------- | ------------------------------------------ |
| `http://localhost`           | EduCore web app (Nginx → Vite → Vue)       |
| `http://localhost/api/health`| Backend health endpoint (Laravel)          |

The Vue app talks to `/api/*`; Nginx forwards API and `storage` traffic to the
Laravel PHP-FPM container and everything else to the Vite dev server.

## 9. Access pgAdmin

| URL                  | Login                        |
| -------------------- | ---------------------------- |
| `http://localhost:5051` | `admin@educore.dev` (or your `PGADMIN_DEFAULT_EMAIL`) |

Password — the value of `PGADMIN_DEFAULT_PASSWORD` (default `educore_admin`).

To register the database server inside pgAdmin: host = `postgres`, port = `5432`,
database/user as in `.env` (`educore`), password — the `POSTGRES_PASSWORD` value.
(`5432` is the port inside the Docker network; the host-facing port is
`${DB_HOST_PORT:-5432}`.)

## 10. Access MinIO console

| URL                  | Purpose        |
| -------------------- | -------------- |
| `http://localhost:9101` | MinIO console  |
| `http://localhost:9100` | S3 API endpoint |

Login: `MINIO_ROOT_USER` / `MINIO_ROOT_PASSWORD` (default `educore` /
`educore_minio_secret`). The `educore` bucket is created automatically on the
first run by the one-shot `minio-init` service.

## 11. Stop the containers

```sh
docker compose down        # stops and removes containers; named volumes persist
docker compose down -v     # also deletes volumes (DB data, MinIO data, cache)
```

## 12. View logs

```sh
docker compose logs -f                    # all services
docker compose logs -f backend            # Laravel only
docker compose logs -f frontend           # Vite only
docker compose logs minio-init            # one-shot bucket creation
```

## 13. Troubleshooting

- **Backend unhealthy / `Connection refused`** — PostgreSQL may still be
  booting; wait a few seconds and check `docker compose ps`.
- **`APP_KEY` error** — remove `backend/.env` (git-ignored) and restart the
  backend container; the entrypoint regenerates the key.
- **Port already in use** — change the matching `*_PORT` value in `.env` and run
  `docker compose up -d` again. PostgreSQL/Redis can be remapped on the host via
  `DB_HOST_PORT` / `REDIS_HOST_PORT` without affecting internal connections.
- **Frontend HMR not reflecting changes** — Vite watches the host files via the
  bind mount; if that fails, restart the frontend container:
  `docker restart educore-frontend`.
- **Image build fails on network** — ensure internet access for `composer`,
  `npm`, and image pulls; retry with `docker compose build --pull`.

## 14. Service & port reference

| Service      | Container name                | Host port                    | Notes                                  |
| ------------ | ----------------------------- | ---------------------------- | -------------------------------------- |
| nginx        | `educore-nginx`               | `${NGINX_PORT:-80}`          | Single entry point                     |
| backend      | `educore-backend`             | — (internal `9000` php-fpm)  | Laravel, bound to `backend:9000`       |
| frontend     | `educore-frontend`            | `${FRONTEND_PORT:-5173}`     | Vite dev server + HMR (via nginx too)  |
| postgres     | `educore-postgres`            | `${DB_HOST_PORT:-5432}`      | PostgreSQL 16, volume `pgdata`         |
| pgadmin      | `educore-pgadmin`             | `${PGADMIN_PORT:-5050}`      | Web UI binds container port `80`       |
| redis        | `educore-redis`               | `${REDIS_HOST_PORT:-6379}`   | Redis 7, AOF enabled                   |
| minio        | `educore-minio`               | `${MINIO_API_PORT:-9000}`    | S3 API                                 |
|              |                               | `${MINIO_CONSOLE_PORT:-9001}`| Web console                            |
| minio-init   | `educore-minio-init`          | —                            | One-shot bucket creation               |

Internal service names (`postgres`, `redis`, `minio`, `backend`, `frontend`,
`nginx`) resolve on the dedicated `educore-network` bridge network and are used
for service-to-service communication inside the stack.

## 15. Useful Make targets

```sh
make help          # list all targets
make up            # start the stack
make logs          # tail all logs
make migrate       # run migrations
make seed          # seed the DB
make shell         # shell into the backend container
make npm cmd="run build"   # run an npm command in the frontend container
```

---

## Project layout

```
backend/            Laravel 12 API (PHP 8.4)
frontend/           Vue 3 + Vite + TypeScript
docker/             Docker images & config (php, frontend, nginx, postgres)
docs/               Project documentation
docker-compose.yml  Local dev environment
.env.docker.example Environment template (copy to .env)
Makefile            Dev helpers
```

## Backend (Laravel)

The `backend/` folder is a standard Laravel 12 application.

- **Docs:** https://laravel.com/docs
- **Bootcamp:** https://bootcamp.laravel.com
- **Videos:** https://laracasts.com
- **Local runtime:** PHP 8.4 with `pgsql`, `pdo_pgsql`, `redis` extensions.
- Installed packages: `laravel/sanctum` (API auth), `laravel/tinker`,
  `league/flysystem-aws-s3-v3` (S3/MinIO storage), pinned via
  `backend/composer.json`.
- API entry point: `backend/routes/api.php` (mounted at `/api`).
- Code style: Laravel Pint (`vendor/bin/pint`). Linted by CI.
- Tests: PHPUnit (`php artisan test`). Linted and run by CI.

## Frontend (Vue 3 + TypeScript + Vite)

The `frontend/` folder is a Vue 3 `<script setup>` SFC project in TypeScript.

- **Docs:** https://vuejs.org/guide/typescript/overview.html
- **Script setup:** https://v3.vuejs.org/api/sfc-script-setup.html
- Build tooling: Vite 8, `vue-tsc`, TypeScript strict checking (`npm run build`).
- Stack: Vue 3, Vue Router 4, Pinia, Axios, Chart.js, Tailwind CSS 4.
- API client lives in `frontend/src/services/api.ts` and talks to `/api`.

## Contributing / License

This project is developed by Rin Nairith & Lyhor. Releases and contribution
guidelines will be tracked via GitHub issues and the `docs/` report.