# EduCore — Docker Development Environment Setup Report

**Report:** 2 of the EduCore project series
**Scope:** Full local Docker setup for the EduCore (Laravel 12 + Vue 3) project
**Date:** 2026-09-24
**Team:** Rin Nairith & Lyhor
**Status:** Implemented and validated

---

# 1. Executive Summary

EduCore now runs entirely inside Docker Compose. Developers no longer need a
local PHP, Node.js, or PostgreSQL installation. One command (`docker compose up -d`)
starts the complete stack:

* `nginx` — single entry point / reverse proxy
* `backend` — Laravel 12 (PHP 8.4-FPM), Composer dependencies installed in-container
* `frontend` — Vue 3 + Vite (Node 22), npm dependencies installed in-container
* `postgres` — PostgreSQL 16, persistent volume
* `pgadmin` — PostgreSQL web admin UI
* `redis` — session, cache, and queue storage
* `minio` — S3-compatible object storage, volume + automatic bucket creation
* `minio-init` — one-shot service that creates the storage bucket on first run

All services are connected to a dedicated bridge network (`educore-network`) and
communicate by service name, never by `localhost`. Every service ships a
healthcheck and startup order is enforced with `depends_on: condition`.

Validated end-to-end: web app served, `/api/health` returns `{"status":"ok"}`,
database migrations run, Redis available, MinIO bucket reachable from Laravel,
and all seven long-running containers report `healthy`.

---

# 2. Objectives

1. Provide a single-command local development environment for the whole stack.
2. Remove the need for local PHP / Node.js / database toolchains.
3. Keep credentials and secrets out of the repository (`.env` is git-ignored).
4. Enforce correct startup order with healthchecks instead of `sleep` hacks.
5. Make every developer's environment identical and reproducible.
6. Match the planned production shape (MySQL-equivalent services, S3 storage) so
   development and deployment stay consistent.

---

# 3. Architecture Overview

```text
                         Your Browser
                              │
                              ▼
                    nginx  :80  (educore-nginx)
                    ┌──────────────────────┐
                    │ /api /storage → PHP-FPM │
                    │ everything else  → Vite │
                    └──────────────────────┘
             ┌───────────────┴────────────────┐
             ▼                                ▼
    backend (Laravel, php-fpm :9000)   frontend (Vite :5173)
      │           │           │
      │           │           └──────────────►  minio :9000 (S3, bucket "educore")
      │           ▼
      │         redis :6379  (session, cache, queues)
      ▼
   postgres :5432  (educore DB)     pgadmin :80  (web UI)
```

All containers join the `educore-network` bridge and use service names
(`postgres`, `redis`, `minio`, `backend`, `frontend`, `nginx`) for
service-to-service communication. No container uses `localhost` to reach
another service.

---

# 4. Files Created / Modified

## New files

| Path | Purpose |
| --- | --- |
| `docker-compose.yml` | Defines all 8 services, network, volumes, healthchecks |
| `.env.docker.example` | Committed environment template (git-ignored copy: `.env`) |
| `.gitignore` | Ignores `.env`, OS/editor files, etc. |
| `.dockerignore` | Root ignore rules for Docker contexts |
| `Makefile` | Development helpers (`make up`, `make migrate`, …) |
| `docker/php/Dockerfile` | PHP 8.4-FPM + extensions + Composer |
| `docker/php/entrypoint.sh` | First-boot provisioning for the backend |
| `docker/php/.dockerignore` | Ignore rules for the backend image build |
| `docker/frontend/Dockerfile` | Node 22 + Vite dev server |
| `docker/frontend/entrypoint.sh` | First-boot `npm install` for the frontend |
| `docker/frontend/.dockerignore` | Ignore rules for the frontend image build |
| `docker/nginx/default.conf` | Reverse proxy config |
| `docker/postgres/init/.gitkeep` | Mount point for future SQL init scripts |

## Modified

| Path | Change |
| --- | --- |
| `frontend/vite.config.ts` | Dev proxy target is now configurable via env |
| `backend/composer.json` | Added `league/flysystem-aws-s3-v3` (S3/MinIO storage) |
| `backend/composer.lock` | Lock file updated for the new package |

## Removed

| Path | Reason |
| --- | --- |
| `backend/README.md` | Stock Laravel boilerplate; content merged into root `README.md` |
| `frontend/README.md` | Stock Vite boilerplate; content merged into root `README.md` |

Documentation now lives in a single root `README.md`.

---

# 5. Services & Port Mapping

| Service | Container name | Host port (default) | Notes |
| --- | --- | --- | --- |
| nginx | `educore-nginx` | `80` | Single entry point |
| backend | `educore-backend` | — (internal `9000`) | Laravel, not exposed to host |
| frontend | `educore-frontend` | `5173` | Vite dev server (HMR via nginx also) |
| postgres | `educore-postgres` | `5433` | Host port configurable (`DB_HOST_PORT`) |
| pgadmin | `educore-pgadmin` | `5051` | Configurable (`PGADMIN_PORT`) |
| redis | `educore-redis` | `6380` | Host port configurable (`REDIS_HOST_PORT`) |
| minio | `educore-minio` | `9100` (API) / `9101` (console) | Configurable (`MINIO_API_PORT`/`MINIO_CONSOLE_PORT`) |
| minio-init | `educore-minio-init` | — | One-shot bucket creation |

> The default ports in the versioned `docker-compose.yml` are the standard ones
> (`5432`, `6379`, `5050`, `9000`, `9001`). The `.env` file used during this
> run remaps the host ports to `5433`, `6380`, `5051`, `9100`, `9101` because a
> separate system already occupies the standard ports. All Remapped values live
> in `.env`, so URLs in the README reflect the active configuration.

## Internal ports (inside the network, never change)

* `postgres:5432` — container port, used by backend and pgAdmin connections
* `redis:6379` — container port, used by backend
* `minio:9000` — S3 API; `minio:9001` — console
* `backend:9000` — PHP-FPM
* `frontend:5173` — Vite

---

# 6. Environment File

All configuration lives in a single root `.env` (git-ignored). The committed
template is `.env.docker.example`.

Copy it once:

```sh
copy .env.docker.example .env     # Windows
cp .env.docker.example .env       # macOS / Linux
```

### Key variables

| Group | Variables |
| --- | --- |
| Application | `APP_NAME`, `APP_ENV=docker`, `APP_DEBUG`, `APP_URL` |
| PostgreSQL | `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_PORT` (internal) |
| PostgreSQL host | `DB_HOST_PORT` (published to your machine) |
| pgAdmin | `PGADMIN_DEFAULT_EMAIL`, `PGADMIN_DEFAULT_PASSWORD`, `PGADMIN_PORT` |
| Redis | `REDIS_HOST`, `REDIS_PORT` (internal), `REDIS_HOST_PORT` (published), `REDIS_PASSWORD` |
| Laravel drivers | `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` |
| MinIO / S3 | `FILESYSTEM_DISK=s3`, `MINIO_ROOT_USER`, `MINIO_ROOT_PASSWORD`, `MINIO_API_PORT`, `MINIO_CONSOLE_PORT` |
| S3 client | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET=educore`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT=true` |
| Frontend | `VITE_API_URL=/api`, `VITE_API_PROXY_TARGET`, `FRONTEND_PORT` |
| Nginx | `NGINX_PORT` |
| Mail | `MAIL_MAILER=log` (dev only) |

### `APP_KEY` strategy

`APP_KEY` is intentionally omitted from `.env` and `.env.docker.example`. On
first backend start the entrypoint generates a stable `base64:` key into
`backend/.env` and keeps it there, so sessions/crypto stay valid across
container restarts.

### Security

`.env` is git-ignored. The committed example ships dev-only placeholder values
that are safe to check in but clearly documented as "never commit real
credentials".

---

# 7. Docker Images

## Backend — `educore/backend:dev`

```dockerfile
FROM php:8.4-fpm
# extensions: pgsql pdo_pgsql pdo_sqlite bcmath intl mbstring zip opcache fileinfo
# pecl: redis
# composer:2 borrowed via multi-stage COPY
ENTRYPOINT ["/usr/local/bin/entrypoint"]   # first-boot provisioning
CMD ["php-fpm"]
```

### `docker/php/entrypoint.sh` — backend first boot

1. Create Laravel runtime dirs (`storage/framework/{sessions,views,cache/data}`).
2. Run `composer install` if `vendor/autoload.php` is missing.
3. Copy `.env.example` to `.env` if absent.
4. Run `php artisan key:generate` if no `APP_KEY=base64:` present.
5. Run `php artisan storage:link --force`.
6. `exec php-fpm` (hand over PID 1).

## Frontend — `educore/frontend:dev`

```dockerfile
FROM node:22-alpine
ENV CHOKIDAR_USEPOLLING=true        # reliable HMR on Windows bind mounts
ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
```

### `docker/frontend/entrypoint.sh` — frontend first boot

1. Run `npm install` if `node_modules` is missing.
2. `exec` the CMD (Vite dev server).

## Nginx — `nginx:alpine`

`docker/nginx/default.conf`:

* `= /nginx-health` — own healthcheck endpoint (returns 200).
* `^~ /api/` — routed to Laravel (`backend:9000`, PHP-FPM).
* `^~ /storage/` — public files with long-lived cache headers.
* `~ \.php$` — FastCGI to `backend:9000`.
* `/` — everything else proxied to `frontend:5173` with WebSocket upgrade for
  Vite HMR.

---

# 8. Startup Order (Healthchecks)

Compose waits for dependencies instead of "sleeping":

* `frontend` → `backend` healthy
* `nginx` → `backend` healthy, `frontend` started
* `backend` → `postgres` healthy, `redis` healthy, `minio-init` completed
* `pgadmin` → `postgres` healthy
* `minio-init` → `minio` healthy

Healthchecks used:

| Service | Check |
| --- | --- |
| nginx | HTTP GET `/nginx-health` |
| backend | Real PDO connection to PostgreSQL from PHP |
| frontend | HTTP GET on the Vite port |
| postgres | `pg_isready` |
| pgadmin | TCP connect to its web port |
| redis | `redis-cli ping` |
| minio | TCP connect to the S3 API port |

Notes:

* The backend healthcheck is a real DB connect — "healthy" means Laravel can
  reach PostgreSQL, not just that PHP-FPM is listening.
* The bash `exec 3<>/dev/tcp/...` healthcheck pattern is used for images that
  ship no `curl`/`wget` (MinIO, pgAdmin) — no extra packages needed.
* `minio-init` is a one-shot `restart: "no"` service that blocks the backend
  until the `educore` bucket exists.

---

# 9. Registry Workarounds

During setup the default images were not reachable:

| Need | Default | Problem | Replacement used |
| --- | --- | --- | --- |
| MinIO server | `minio/minio:latest` | Image removed from Docker Hub | `coollabsio/minio:latest` |
| MinIO client (`mc`) | `quay.io/minio/mc:latest` | `quay.io` returns 401 in this environment | `amazon/aws-cli:latest` |

`coollabsio/minio` is a community rebuild of the official MinIO binary and uses
the standard `MINIO_ROOT_USER` / `MINIO_ROOT_PASSWORD` environment variables.

Bucket creation is now done with the AWS CLI against the endpoint:

```sh
until aws --endpoint-url http://minio:9000 s3 ls >/dev/null; do sleep 2; done
aws --endpoint-url http://minio:9000 s3 mb --region us-east-1 s3://educore
```

---

# 10. Volumes

Persistent named volumes keep data across `docker compose down`:

| Volume | Mounted at | Holds |
| --- | --- | --- |
| `pgdata` | `/var/lib/postgresql/data` | PostgreSQL data |
| `pgadmin-data` | `/var/lib/pgadmin` | pgAdmin config/sessions |
| `redisdata` | `/data` | Redis AOF persistence |
| `minio-data` | `/data` | Object storage |
| `backend-vendor` | `/var/www/html/vendor` | Composer packages |
| `frontend-node-modules` | `/app/node_modules` | npm packages |

The `vendor` and `node_modules` volumes sit on top of `./backend` /
`./frontend` bind mounts, so host edits are live-reloaded without re-running
`composer install` / `npm install`.

`docker compose down -v` deletes everything (fresh-clone state).

---

# 11. Makefile

```sh
make help            # list all targets
make up              # docker compose up -d
make down            # stop & remove containers (volumes kept)
make restart         # restart all services
make ps              # show running containers
make logs            # tail all logs
make build           # rebuild images
make migrate         # php artisan migrate
make migrate-fresh   # migrate:fresh --seed
make seed            # php artisan db:seed
make shell           # bash into backend
make frontend-shell  # sh into frontend
make pint            # run Laravel Pint
make test            # run backend tests
make npm cmd="run build"   # arbitrary npm command in frontend
make npm-install     # npm install
make npm-build       # npm run build
make clear-cache     # php artisan optimize:clear
```

---

# 12. Validation Results

Executed against the running stack on 2026-09-24.

## Services

```text
SERVICE    STATUS
backend    Up (healthy)
frontend   Up (healthy)
minio      Up (healthy)
nginx      Up (healthy)
pgadmin    Up (healthy)
postgres   Up (healthy)
redis      Up (healthy)
```

## Verification matrix

| Check | Result |
| --- | --- |
| `docker compose config --quiet` | Exit 0, valid config |
| `http://localhost/nginx-health` | 200 |
| `http://localhost/` | 200 — Vue app HTML via nginx → Vite |
| `http://localhost:5173/` | 200 — Vite dev server directly |
| `http://localhost:5173/api/health` | 200 `{"status":"ok"}` (Vite proxy → nginx → Laravel) |
| `http://localhost/api/health` | 200 `{"status":"ok"}` (nginx → PHP-FPM → Laravel) |
| `php artisan migrate --force` | All migrations applied (users, cache, jobs, personal tokens) |
| Redis from backend | `PONG` / `1` |
| MinIO from backend | Bucket list returns `educore` |
| `php artisan storage:link` | Already linked (idempotent) |
| `APP_KEY` auto-generation | `base64:` key present in `backend/.env` |
| Laravel version | 12.69.2, PHP 8.4.25, Composer 2.10.3 |
| pgAdmin | Responds / boots (login page) |
| minio-init logs | `make_bucket: educore` → bucket ready |

---

# 13. Day-to-Day Commands

```sh
docker compose up -d                     # start everything
docker compose ps                        # status
docker compose logs -f backend           # watch Laravel logs
docker compose exec backend php artisan tinker
docker compose exec backend php artisan test
docker compose exec frontend npm run build
docker compose down                      # stop, keep data
docker compose down -v                   # stop and wipe data
```

While hacking, edit files under `backend/` or `frontend/` — the compose stack
bind-mounts them and Laravel/Vite pick changes up immediately.

---

# 14. Troubleshooting Notes (from this setup)

* **MinIO image unavailable** — `minio/minio` is gone from Docker Hub and
  `quay.io` is blocked here; use `coollabsio/minio` (this project's choice).
* **Healthcheck `curl` missing** — some images (MinIO rebuild, pgAdmin) ship no
  `curl`/`wget`; the compose healthcheck uses a bash `/dev/tcp` probe instead.
* **Host port conflict** — if a port is taken (a second project's stack, a local
  service), change `DB_HOST_PORT` / `REDIS_HOST_PORT` / `PGADMIN_PORT` /
  `MINIO_API_PORT` / `MINIO_CONSOLE_PORT` in `.env`. Internal connections are
  unaffected because they use the container ports.
* **`APP_KEY` error** — delete `backend/.env` (git-ignored) and restart the
  backend; the entrypoint regenerates it.
* **Backend unhealthy right after `up`** — PostgreSQL may still be booting; the
  healthcheck retries automatically.
* **Frontend HMR not picking up changes** — bind-mount watcher limits on
  Windows; `CHOKIDAR_USEPOLLING=true` is already set in the frontend image.

---

# 15. Security & Good Practices Applied

* `APP_KEY` and all passwords live only in git-ignored `.env` files.
* The backend container is not exposed to the host; everything goes through
  nginx.
* Least-published surface: backend FPM and `minio-init` have no host ports.
* Meaningful healthchecks gate startup; no blind `sleep`s.
* `restart: unless-stopped` keeps services recoverable, one-shot init uses
  `restart: "no"`.
* Credentials use isolated MinIO/S3 keys separate from any production values.
* Dev mailer is `log`; no real SMTP credentials are configured.

---

# 16. What Comes Next

1. **Seed the database** with base data (roles, admin account, academic
   structure) via `database/seeders`.
2. **Add the first real API feature** (authentication with Sanctum, then
   faculties → departments → programs → courses) and wire it into the Vue UI.
3. **Wire object storage** into the app: document uploads, profile photos,
   assignment files → MinIO `educore` bucket.
4. **CI parity** — `.github/workflows` (already present) can reuse the same
   service healthchecks/drivers so tests run against the same shape as local
   dev.
5. **Production Docker config** — a future `docker-compose.prod.yml` /
   Dockerfile for Laravel Cloud (already planned; dev and prod diverge on
   purpose).

---

# Project Summary

| Item | Result |
| --- | --- |
| Entry point | `http://localhost` (nginx) |
| Backend | Laravel 12, PHP 8.4-FPM, container-only |
| Frontend | Vue 3 + Vite, Node 22 |
| Database | PostgreSQL 16 (host `5433`) |
| DB admin | pgAdmin (host `5051`) |
| Cache/queue | Redis 7 (host `6380`) |
| Object storage | MinIO (host `9100`/`9101`), auto-created `educore` bucket |
| Networking | Dedicated bridge `educore-network`, service-name DNS |
| Healthchecks | All long-running services; backend checks real DB connect |
| First-run provisioning | Composer/npm install, `APP_KEY`, storage link, bucket |
| Data persistence | 6 named volumes |
| Secrets | Git-ignored `.env`; example template committed |
| Commands | `docker compose up -d` / Makefile helpers |