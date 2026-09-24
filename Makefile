# EduCore - development helpers
# Usage: make up  |  make migrate  |  ...
# Requires Docker + Docker Compose.

COMPOSE := docker compose
EXEC_BACKEND := $(COMPOSE) exec backend
EXEC_FRONTEND := $(COMPOSE) exec frontend

.PHONY: help up down restart ps logs build \
        migrate migrate-fresh seed shell frontend-shell \
        pint test npm npm-install npm-build \
        postgres-redis-storage clear-cache

help: ## Show available commands
	@echo "EduCore dev commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

up: ## Start the stack (nginx, backend, frontend, postgres, pgadmin, redis, minio)
	$(COMPOSE) up -d

down: ## Stop and remove containers (volumes kept)
	$(COMPOSE) down

restart: ## Restart all services
	$(COMPOSE) restart

ps: ## Show running containers
	$(COMPOSE) ps

logs: ## Tail logs from all services
	$(COMPOSE) logs -f

build: ## Rebuild images
	$(COMPOSE) build

migrate: ## Run database migrations
	$(EXEC_BACKEND) php artisan migrate

migrate-fresh: ## Drop all tables and re-migrate
	$(EXEC_BACKEND) php artisan migrate:fresh --seed

seed: ## Seed the database
	$(EXEC_BACKEND) php artisan db:seed

shell: ## Open a shell in the backend container
	$(EXEC_BACKEND) bash

frontend-shell: ## Open a shell in the frontend container
	$(EXEC_FRONTEND) sh

pint: ## Run Laravel Pint linter
	$(EXEC_BACKEND) vendor/bin/pint

test: ## Run backend tests
	$(EXEC_BACKEND) php artisan test

npm: ## Run an npm command in the frontend container (e.g. make npm cmd="install")
	$(EXEC_FRONTEND) npm $(cmd)

npm-install: ## Install frontend dependencies
	$(EXEC_FRONTEND) npm install

npm-build: ## Build frontend assets
	$(EXEC_FRONTEND) npm run build

postgres-redis-storage:
	@echo "postgres/redis/minio run inside Docker Compose."

clear-cache: ## Clear all Laravel caches
	$(EXEC_BACKEND) php artisan optimize:clear