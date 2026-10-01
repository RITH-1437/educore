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
and never committed. The template is `docker/.env.docker.example`.

Variables group by responsibility:

- `APP_*` — Laravel application settings.
- `POSTGRES_*` / `PGADMIN_*` — database service credentials and host ports.
- `REDIS_*` — Redis host ports.
- `MINIO_*` / `AWS_*` — object storage credentials, bucket name, API/console ports.
- `NGINX_PORT` / `FRONTEND_PORT` — host ports for the web entry point.
- `VITE_API_URL` — frontend REST API base path (`/api`).
- `FRONTEND_URL` / `VITE_DEV_SERVER_URL` — front-end connection origins
  (same-origin defaults; share via `config/frontend.php` and Inertia props).

> Note: `APP_KEY` is intentionally omitted. It is auto-generated into
> `backend/.env` on the first container start and persists thereafter.

## 3. Copy the env template to `.env`

```sh
# Windows (PowerShell)
copy docker\.env.docker.example .env

# macOS / Linux
cp docker/.env.docker.example .env
```

Adjust the placeholder credentials (PostgreSQL password, MinIO keys, pgAdmin
login) to your liking before the first `up`.

## 4. Start the containers

The Compose file lives at `docker/docker-compose.yml`. Run it from the repo
root with `--project-directory .` so it reads the root `.env`:

```sh
docker compose --project-directory . -f docker/docker-compose.yml up -d
```

(`make up` does exactly this.) First run builds the `backend` and `frontend` images, installs Composer
dependencies and `npm` dependencies inside the containers, creates the MinIO
bucket, and starts everything. Subsequent starts are fast.

## 5. Check status

```sh
docker compose --project-directory . -f docker/docker-compose.yml ps
```

All services should show `healthy` (`/healthy` for minio-init). See `make ps`.

## 6. Run database migrations

```sh
docker compose --project-directory . -f docker/docker-compose.yml exec backend php artisan migrate
```

Or via Make: `make migrate`.

## 7. Seed the database

```sh
docker compose --project-directory . -f docker/docker-compose.yml exec backend php artisan db:seed
```

Or via Make: `make seed`. To drop and re-seed in one step: `make migrate-fresh`.

The seed is idempotent and produces: 1 current university (`ITC`), 3 faculties
(`ENG`, `SCI`, `HSS`) and 6 departments, plus roles and a super-admin
(`admin@educore.kh` / `admin@123`). See
[`docs/database/seed-strategy.md`](docs/database/seed-strategy.md).

## 8. Access the application

| URL                          | Purpose                                    |
| ---------------------------- | ------------------------------------------ |
| `http://localhost`           | EduCore web app (Nginx → Laravel/Inertia → Vue) |
| `http://localhost:5173`      | Vite dev server (HMR, dev assets)          |
| `http://localhost/api/health`| Backend health endpoint (Laravel)          |
| `http://localhost/api/documentation` | Swagger UI — interactive API reference |
| `http://localhost/docs`      | Generated OpenAPI 3.0 document (JSON)      |

### Admin routes

| Path                        | Page                       | Who can write |
| --------------------------- | -------------------------- | ------------- |
| `/admin/dashboard`          | Admin dashboard            | — |
| `/users`                    | Users management           | Super admin   |
| `/academic-years`           | Academic years & semesters | Super admin, University admin |
| `/universities`             | University record          | Super admin, University admin |
| `/universities/{id}/edit`   | Edit university            | Super admin, University admin |
| `/faculties`                | Faculties + departments    | Super admin, University admin |

Faculty Admin has **read-only** access to `/universities` and `/faculties`; the
write controls are hidden in the UI and the routes still reject the request with
`403`. Report:
[`docs/7_Faculty-and-Department-Report.md`](docs/7_Faculty-and-Department-Report.md).

Pages are rendered by Laravel through Inertia.js. Nginx forwards `/api`,
`/storage`, and non-existing paths to the Laravel PHP-FPM container, and serves
built assets (`.`/`/build`) statically from the backend `public/`.

The API reference is generated from the code itself: OpenAPI attributes live in
`backend/app/OpenApi/` and on the API controllers, and the document is rebuilt
with:

```bash
docker compose --project-directory . -f docker/docker-compose.yml \
  exec -T backend php artisan l5-swagger:generate
```

After changing any request validation, response shape, or endpoint, regenerate
the document so `docs/` and Swagger stay in sync.

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
# (assumed: docker compose --project-directory . -f docker/docker-compose.yml)
docker compose --project-directory . -f docker/docker-compose.yml down        # stops and removes containers; named volumes persist
docker compose --project-directory . -f docker/docker-compose.yml down -v     # also deletes volumes (DB data, MinIO data, cache)
```

## 12. View logs

```sh
docker compose --project-directory . -f docker/docker-compose.yml logs -f                    # all services
docker compose --project-directory . -f docker/docker-compose.yml logs -f backend            # Laravel only
docker compose --project-directory . -f docker/docker-compose.yml logs -f frontend           # Vite only
docker compose --project-directory . -f docker/docker-compose.yml logs minio-init            # one-shot bucket creation
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
| frontend     | `educore-frontend`            | `${FRONTEND_PORT:-5173}`     | Vite dev server + HMR                |
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
frontend/           Vue 3 + Inertia + Vite (JavaScript)
docker/             Docker config (docker-compose.yml, env template, php/frontend/nginx/postgres images)
docs/               Project documentation
.env                Local environment (git-ignored; copy of docker/.env.docker.example)
Makefile            Dev helpers
```

## Backend (Laravel)

The `backend/` folder is a standard Laravel 12 application.

- **Docs:** https://laravel.com/docs
- **Bootcamp:** https://bootcamp.laravel.com
- **Videos:** https://laracasts.com
- **Local runtime:** PHP 8.4 with `pgsql`, `pdo_pgsql`, `redis` extensions.
- Installed packages: `laravel/sanctum` (API auth), `laravel/tinker`,
  `league/flysystem-aws-s3-v3` (S3/MinIO storage),
  `darkaonline/l5-swagger` (OpenAPI document + Swagger UI), pinned via
  `backend/composer.json`.
- API entry point: `backend/routes/api.php` (mounted at `/api`).
- Layering (dependencies point downwards only):
  `routes → controllers → services → repositories → models`.
  - `app/Http/Controllers` — thin: authorize, map input, call a service, return
    a resource.
  - `app/Http/Requests` — validation; `app/Http/Resources` — JSON shape.
  - `app/Dto` — typed input/response objects (`CreateUserData`, `UserListFilters`,
    `UserData`, `LoginResult`) passed between HTTP and service layers.
  - `app/Services` — business logic and transaction boundaries.
  - `app/Repositories` — data access (query building, writes, token revocation).
  - `app/Policies` — authorization.
- Code style: Laravel Pint (`vendor/bin/pint`). Linted by CI.
- Tests: PHPUnit (`php artisan test`). Linted and run by CI. Note: the suite uses
  `RefreshDatabase` against the configured database, so it **wipes local data** —
  re-run `php artisan db:seed` afterwards if you need the demo admin account.

## Frontend (Vue 3 + Inertia + Vite)

The `frontend/` folder is a Vue 3 `<script setup>` SFC project in plain
JavaScript, embedded via **Inertia.js**: Laravel controllers return
`Inertia::render('Page')` and the matching component lives in
`frontend/src/pages/`.

- **Docs:** https://inertiajs.com, https://vuejs.org/guide/typescript/overview.html
- **Routing:** Inertia (server-side) — no client `vue-router`.
- Build tooling: Vite 8 (`npm run build`); assets + manifest are written to
  `backend/public/build` (`laravel-vite-plugin`) and served to the page by
  Laravel's `@vite`.
- Stack: Vue 3, Inertia.js, Pinia, Axios, Chart.js, Tailwind CSS 4.
- REST API client lives in `frontend/src/services/api.js` and talks to `/api`.

## Contributing / License

This project is developed by Rin Nairith & Yong Lyhor. Releases and contribution
guidelines will be tracked via GitHub issues and the `docs/` report.