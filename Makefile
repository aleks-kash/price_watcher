# ==============================================================================
# Price Watcher (Цінозор) - Makefile
# ==============================================================================

DC = docker compose
APP = $(DC) exec app

# Cross-platform helpers
ifeq ($(OS),Windows_NT)
CP_ENV = if not exist .env copy .env.example .env
HELP_CMD = powershell -NoProfile -Command "[Console]::OutputEncoding = [System.Text.Encoding]::UTF8; Get-Content -Encoding UTF8 $(MAKEFILE_LIST) | Select-String '^[a-zA-Z_-]+:.*?\x23\x23 ' | ForEach-Object { $$p = $$_.Line -split ':\s*\x23\x23\s*'; Write-Host ('  ' + $$p[0].PadRight(15)) -NoNewline -ForegroundColor Cyan; Write-Host (' ' + $$p[1]) }"
else
CP_ENV = test -f .env || cp .env.example .env
HELP_CMD = grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'
endif

.DEFAULT_GOAL := help

.PHONY: help install up down restart ps logs test

help: ## Відобразити список доступних команд / Show help message
	@echo "Price Watcher Makefile commands:"
	@$(HELP_CMD)

# ------------------------------------------------------------------------------
# Встановлення та ініціалізація / Installation & Initialization
# ------------------------------------------------------------------------------

install: ## Повна установка: копіювання .env, збірка контейнерів, composer, ключ, міграції та сідери
	@$(CP_ENV)
	$(DC) up -d --build
	$(APP) composer install --no-interaction
	$(APP) php artisan key:generate --force
	$(APP) php artisan migrate --force
	$(APP) php artisan db:seed --force
	@echo "---------------------------------------------------------------"
	@echo "Installation completed successfully!"
	@echo "API Web Server:      http://localhost:8080"
	@echo "Swagger UI Docs:     http://localhost:8080/docs/api"
	@echo "OpenAPI JSON Spec:   http://localhost:8080/docs/api.json"
	@echo "---------------------------------------------------------------"

# ------------------------------------------------------------------------------
# Запуск та зупинка / Start & Stop
# ------------------------------------------------------------------------------

up: ## Запустити всі Docker-контейнери у фоновому режимі
	$(DC) up -d

down: ## Зупинити всі контейнери
	$(DC) down

restart: ## Перезапустити всі контейнери
	$(DC) restart

ps: ## Переглянути статус контейнерів
	$(DC) ps

logs: ## Перегляд логів контейнерів у реальному часі
	$(DC) logs -f

# ------------------------------------------------------------------------------
# Тестування / Testing
# ------------------------------------------------------------------------------

test: ## Запустити всі автоматизовані тести (опційно: make test ARGS="--filter=...")
	$(APP) php artisan test $(ARGS)