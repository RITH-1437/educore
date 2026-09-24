---
name: educore-docker
description: EduCore Docker Compose architecture - services, internal hostnames, networks, volumes, healthchecks, env vars. Consult before changing any container config.
---

# EduCore Docker

Development runs entirely in Docker Compose. No local PHP/Node/Postgres
required. Config lives in `docker-compose.yml`, `docker/`, and `.env`.

## When to use

- Changing containers, images, networks, volumes, healthchecks, env vars, or
  the build/entrypoint scripts.

## Services & internal hostnames

| Service (compose) | Internal hostname | Internal port | Role |
| --- | --- | --- | --- |
| nginx | `nginx` | 80 | Single entry point / reverse proxy |
| backend | `backend` | 9000 (php-fpm) | Laravel |
| frontend | `frontend` | 5173 (Vite) | Vue dev server |
| postgres | `postgres` | 5432 | PostgreSQL 16 |
| pgadmin | `pgadmin` | 80 | DB admin UI |
| redis | `redis` | 6379 | Cache/queue/session |
| minio | `minio` | 9000/9001 | S3 storage + console |
| minio-init | `minio-init` | — | one-shot bucket creation |

## Golden rule

**Never use `localhost` when one container reaches another.** Use the internal
service name (`postgres`, `redis`, `minio`, `backend`, `frontend`). `localhost`
refers to the container itself.

Examples:
- Backend → DB: `DB_HOST=postgres`
- Backend → cache: `REDIS_HOST=redis`
- Backend → S3: `AWS_ENDPOINT=http://minio:9000`
- Vite proxy → API: `http://nginx:80`
- Nginx → php-fpm: `fastcgi_pass backend:9000`
- Nginx → Vite: `proxy_pass http://frontend:5173`

## Network

- Dedicated bridge network `educore-network`. Every service joins it.
- Do not rely on the default compose network; keep isolation.

## Volumes

- `pgdata`, `pgadmin-data`, `redisdata`, `minio-data` — persistent data.
- `backend-vendor` → `/var/www/html/vendor`, `frontend-node-modules` →
  `/app/node_modules` — mounted over the bind mounts so host code edits are
  live but dependencies live in the container.
- Bind mounts: `./backend`, `./frontend`, plus `docker/nginx/default.conf`.
- `docker compose down` keeps volumes; `down -v` wipes data.

## Healthchecks & startup order

- Every long-running service has a meaningful healthcheck; `depends_on`
  uses `condition: service_healthy` (or `service_completed_successfully` for
  `minio-init`).
- Backend healthcheck performs a **real PDO connect** to Postgres (not just
  php-fpm listening).
- Images without `curl`/`wget` (MinIO, pgAdmin) use a bash `/dev/tcp` probe.
- Startup order: postgres/redis/minio healthy → minio-init creates bucket →
  backend healthy → nginx starts.

## Environment variables

- All config in root `.env` (git-ignored; template `.env.docker.example`).
- Compose reads it via `env_file` (backend) and `${VAR:-default}` substitution.
- Backend container overrides: `DB_HOST`, `REDIS_HOST`, `AWS_ENDPOINT`,
  `FILESYSTEM_DISK=s3`, `SESSION_DRIVER/CACHE_STORE/QUEUE_CONNECTION=redis`.
- `.env` is never committed; **never hardcode secrets** in `docker-compose.yml`
  (use `${VAR:-default}` so real values stay in `.env`).

## Development containers

- `backend`: PHP 8.4-FPM; entrypoint runs `composer install`, copies
  `.env.example`→`.env`, `key:generate`, `storage:link` on first boot.
- `frontend`: Node 22; entrypoint runs `npm install` on first boot; serves Vite
  with `CHOKIDAR_USEPOLLING=true` (Windows bind mounts).
- Images: `educore/backend:dev`, `educore/frontend:dev`.

## Common commands

```sh
docker compose up -d           # start
docker compose ps              # status (expect "healthy")
docker compose exec backend php artisan migrate
docker compose logs -f backend
docker compose down            # stop (keep data)
docker compose build           # rebuild images
```

(Or the equivalent `make` targets: `up`, `ps`, `migrate`, `logs`, `down`.)

## Prohibitions

- DO NOT use `localhost` for cross-container calls.
- DO NOT hardcode credentials in compose/Dockerfiles.
- DO NOT remove healthchecks or weaken `depends_on` conditions.
- DO NOT edit `docker-compose.prod.yml` thinking it is the dev file (production
  is Laravel Cloud + managed services).
- DO NOT commit `.env`.

## Validation checklist

1. `docker compose config --quiet` passes.
2. New service joins `educore-network` and has a healthcheck if long-running.
3. Cross-container refs use service names, not localhost.
4. Secrets read from `.env`, not literals.
5. Related skills respected: `laravel`, `vue`, `security`.

## Agent behavior (mandatory everywhere)

1. Inspect the existing implementation before modifying it.
2. Follow existing project conventions already established.
3. Do not rewrite working code unnecessarily.
4. Do not introduce technologies outside the EduCore stack.
5. Do not create unnecessary abstractions.
6. Do not create duplicate business logic.
7. Do not invent database relationships.
8. Do not bypass authorization.
9. Do not hardcode secrets.
10. Do not modify unrelated modules.
11. Run appropriate tests after changes.
12. Explain important architectural decisions.