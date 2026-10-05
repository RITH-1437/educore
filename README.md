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
  Compose passes the MinIO root credentials and bucket to the backend as `AWS_*`.
- `UPLOADS_DISK` — filesystem disk for private uploads such as assignment
  submissions (default `s3` = MinIO).
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

The seed is idempotent and produces only what a fresh installation needs: the
five roles, **one account per role** — `admin@educore.kh` / `admin@123`
(Super Admin), `university@educore.kh` / `university@123`, `department@educore.kh`
/ `department@123` (Department Admin), `lecturer@educore.kh` / `lecturer@123` and
`student@educore.kh` / `student@123` (development only — change them) — plus
the default grading scale and the requestable document types. No demo people or records are seeded: universities, departments,
programs, courses, students, lecturers and everything else are entered through
the application, and every screen (including the public landing page's figures)
shows what is in the database. See
[`docs/database/seed-strategy.md`](docs/database/seed-strategy.md).

Optional: `MAX_SEMESTER_CREDITS` (default `24`) sets the per-semester credit
limit used by enrollment, and `MATERIAL_MAX_KB` (default `20480`) the largest
course-material upload (`backend/config/academics.php`).

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
| `/dashboard`                | Student academic dashboard (GPA, credits, attendance, today's classes, due work, exams, grades); Department admin: requests waiting for them and their department's headline numbers; Lecturer and University admin: their own dashboards. A student account not yet linked to a profile sees a "No student profile" notice | — |
| `/users`                    | Users management (assign a Department Admin's department) | Super admin   |
| `/academic-years`           | Academic years & semesters | Super admin, University admin |
| `/universities`             | University record          | Super admin, University admin |
| `/universities/{id}/edit`   | Edit university            | Super admin, University admin |
| `/departments`              | Departments (archive, reactivate, delete guards) | Super admin, University admin (Department admin read-only, own department) |
| `/programs`                 | Programs (degree tracks)   | Super admin, University admin (Department admin read-only, own department) |
| `/programs/{id}/edit`       | Edit program               | Super admin, University admin |
| `/courses`                  | Course catalog             | Super admin, University admin (Department admin read-only, own department) |
| `/courses/{id}/edit`        | Edit course + prerequisites | Super admin, University admin |
| `/attendance`               | Take attendance (my sections) | Lecturer |
| `/attendance/sections/{id}` | Attendance register + rates | Super admin, University admin, Department admin (read, own department), the section's lecturers |
| `/my-attendance`            | My attendance per course   | Student |
| `/coursework/sections/{id}` | Section assignments, submissions, grading | Super admin, University admin, Department admin (read, own department), the section's lecturers, enrolled students (submit) |
| `/my-assignments`           | My assignments + uploads   | Student |
| `/my-materials`             | Course materials of my sections (files, links) | Student (read); shared on `/coursework/sections/{id}` by its lecturers |
| `/exams/sections/{id}`      | Section exams + results grid | Super admin, University admin, Department admin (read, own department), the section's lecturers; enrolled students (schedule, released results) |
| `/my-exams`                 | My exam schedule + results | Student |
| `/grades`                   | Sections awaiting grade approval | Super admin, University admin (Department admin read-only, own department) |
| `/grades/sections/{id}`     | Section grade sheet: compute, submit, approve, return, finalize, reopen | Super admin (reopen), University admin (approve, finalize), Department admin (read, own department), the section's lecturers (compute, submit) |
| `/grading-scale`            | Grading scale              | Super admin, University admin (Department admin and lecturers read-only) |
| `/my-grades`                | My grades + semester / cumulative GPA | Student |
| `/my-documents`             | Request documents, download PDFs | Student |
| `/documents`                | Document request queue: approve, reject, generate, revoke, waive fee | Super admin, University admin; Department admin for their department's students (no revoke) |
| `/document-types`           | Requestable document types and fees | Super admin, University admin |
| `/verify/{code}`            | Public document verification (no sign-in, rate limited) | — |
| `/invoices`                 | Invoices: create, edit, record / reverse payments, cancel, export CSV | Super admin, University admin |
| `/my-invoices`              | My invoices, payments and balance | Student |
| `/announcements`            | Announcement feed (own audience) | Every signed-in role |
| `/announcements/manage`     | Write, publish, archive announcements | Super admin, University admin (any audience), Lecturer (own sections / courses) |
| `/notifications`            | My notification settings (email opt-out, Telegram chat) | Every signed-in user (own only) |
| `/inbox`                    | My in-app notifications (bell in the top bar): open, mark read | Every signed-in user (own only) |
| `/my-internships`           | Apply for an internship, follow it, submit reports | Student |
| `/internships`              | Internship queue: review, approve, start, complete, evaluate | Super admin, University admin; Department admin for their department's students |
| `/internship-companies`     | Host companies | Super admin, University admin (Department admin read-only) |
| `/analytics`                | Analytics: enrollment, academic performance, workload; CSV per table | Super admin, University admin |
| `/audit-logs`               | Audit trail (read-only): sign-ins and sensitive changes; CSV export | Super admin |
| `/account/password`         | Change my password (other sessions are signed out) | Every signed-in user |
| `/forgot-password`          | Request a password reset link by email (rate limited) | Guests |
| `/rooms`                    | Rooms                      | Super admin, University admin (Department admin read-only, own department) |
| `/timetable`                | My weekly timetable        | Student, Lecturer |
| `/enrollments`              | Enrollment management; export CSV | Super admin, University admin (Department admin read-only + export, own department) |
| `/registration`             | Course registration (self-service) | Student |
| `/offerings`                | Offerings & sections       | Super admin, University admin (Department admin read-only, own department) |
| `/offerings/{id}`           | Manage sections + lecturers | Super admin, University admin |
| `/students`                 | Student profiles, status, program | Super admin, University admin (Department admin read-only, own department) |
| `/students/{id}/edit`       | Manage student             | Super admin, University admin |
| `/lecturers`                | Lecturer profiles + accounts | Super admin, University admin (Department admin read-only, own department) |
| `/lecturers/{id}/edit`      | Edit lecturer              | Super admin, University admin |
| `/error-logs`                | System error logs (404/5xx, read-only) | Super admin only (no write) |

Department Admin has **read-only** access to `/universities`, `/departments`, `/programs`, `/courses`, `/lecturers` and `/students`, limited to their
department; the write controls are hidden in the UI and the routes still reject the request with
`403`. Reports:
[`docs/7_Faculty-and-Department-Report.md`](docs/7_Faculty-and-Department-Report.md),
[`docs/39_Department-Only-Structure-Report.md`](docs/39_Department-Only-Structure-Report.md).

`/error-logs` is not on the sidebar — it is a diagnostic tool reached by typing
the URL directly, recording only HTTP 404/5xx responses (never 401/403/409/422,
query strings, request bodies, or 404s of browser / desktop-webview probes).
Old `/faculties` links redirect to `/departments`. Report:
[`docs/8_System-Error-Logs-Report.md`](docs/8_System-Error-Logs-Report.md).

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

- **Tests refuse to run: `Refusing to refresh database [educore]`** — the
  Laravel config is cached (`backend/bootstrap/cache/config.php`). Run
  `docker compose --project-directory . -f docker/docker-compose.yml exec backend php artisan optimize:clear`.
  If it comes back after every restart, the `educore/backend:dev` image
  predates the entrypoint fix — rebuild it and recreate the PHP containers:
  `docker compose --project-directory . -f docker/docker-compose.yml build backend`
  then `... up -d backend queue scheduler`. Rebuild whenever
  `docker/php/entrypoint.sh` or `docker/php/Dockerfile` changes.

- **Tests fail: `database "educore_test" does not exist`** — the init script
  only runs when the PostgreSQL volume is first created. For an older volume,
  create the test database once:
  `docker compose --project-directory . -f docker/docker-compose.yml exec postgres createdb -U educore educore_test`.

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
| queue        | `educore-queue`               | —                            | `queue:work` (notifications, default)  |
| scheduler    | `educore-scheduler`           | —                            | `schedule:work` (daily commands)       |

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
  `darkaonline/l5-swagger` (OpenAPI document + Swagger UI),
  `barryvdh/laravel-dompdf` (official document PDFs), pinned via
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
- Scheduled commands (`routes/console.php`, run by the `scheduler` container):
  `php artisan invoices:refresh-statuses` (daily 00:10, marks unpaid past-due
  invoices overdue) and `php artisan notifications:assignment-reminders`
  (daily 07:00) and `php artisan notifications:prune` (daily 01:00, deletes
  in-app notifications older than 180 days). Queued jobs (notifications) run in the `queue` container;
  watch them with `docker compose --project-directory . -f docker/docker-compose.yml logs -f queue`.
- Notifications: email via `MAIL_MAILER` (`log` in development — messages land
  in `storage/logs/laravel.log`); Telegram via `TELEGRAM_BOT_TOKEN` in `.env`
  (empty disables the channel; never commit a real token).
- Tests: PHPUnit (`php artisan test`). Linted and run by CI. The suite runs
  against its own `educore_test` database (`phpunit.xml`; created by
  `docker/postgres/init/01-create-test-database.sh`; host and credentials come
  from the environment — the containers locally, `127.0.0.1` / `postgres` in
  CI), and `tests/TestCase`
  refuses to refresh any database whose name does not end in `_test`, so your
  development data is never touched. Tests log nowhere (`LOG_CHANNEL=null`):
  `storage/logs/laravel.log` only holds development entries.

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