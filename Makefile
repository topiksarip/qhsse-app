# =============================================================================
# QHSSE App — Makefile
# Usage: make <target>  |  make help
# =============================================================================

.PHONY: help up down restart build rebuild logs shell \
        migrate seed fresh test tinker artisan \
        push push-develop push-main feature status log diff \
        db-shell redis-shell npm-dev npm-build

.DEFAULT_GOAL := help

REPO   := https://github.com/topiksarip/qhsse-app
BRANCH := $(shell git branch --show-current 2>/dev/null || echo main)
MSG    ?= "chore: update $(shell date '+%Y-%m-%d %H:%M')"
DC     := docker compose

##@ Docker — Setup & Lifecycle

up: ## Jalankan semua container (build otomatis jika belum ada)
	$(DC) up -d --remove-orphans
	@echo "✅  App: http://localhost:$$(grep APP_PORT .env 2>/dev/null | cut -d= -f2 || echo 8080)"

down: ## Stop dan hapus container (volume tetap ada)
	$(DC) down

restart: ## Restart semua container
	$(DC) restart

build: ## Build image tanpa cache (pakai saat Dockerfile/deps berubah)
	$(DC) build --no-cache

rebuild: down build up ## Full rebuild: down → build → up

logs: ## Stream logs semua container (Ctrl+C untuk keluar)
	$(DC) logs -f

logs-app: ## Stream logs hanya container app
	$(DC) logs -f app

logs-nginx: ## Stream logs hanya nginx
	$(DC) logs -f nginx

##@ Docker — Shell & Debug

shell: ## Masuk shell container app (bash)
	$(DC) exec app bash

shell-root: ## Masuk shell container app sebagai root
	$(DC) exec -u root app bash

db-shell: ## Masuk psql di container postgres
	$(DC) exec postgres psql -U $$(grep DB_USERNAME .env | cut -d= -f2 || echo qhsse) \
	      $$(grep DB_DATABASE .env | cut -d= -f2 || echo qhsse)

redis-shell: ## Masuk redis-cli di container redis
	$(DC) exec redis redis-cli -a $$(grep REDIS_PASSWORD .env | cut -d= -f2 || echo redissecret)

##@ Laravel (via Docker)

artisan: ## Jalankan artisan command. Contoh: make artisan CMD="route:list"
	$(DC) exec app php artisan $(CMD)

migrate: ## php artisan migrate
	$(DC) exec app php artisan migrate --force

seed: ## php artisan db:seed
	$(DC) exec app php artisan db:seed --force

fresh: ## migrate:fresh --seed (HAPUS semua data!)
	@echo "⚠️  Ini akan menghapus semua data. Lanjut? [y/N] " && read ans && [ $${ans:-N} = y ]
	$(DC) exec app php artisan migrate:fresh --seed --force

test: ## Jalankan test suite (SQLite in-memory, tidak butuh postgres)
	@if [ -n "$$($(DC) ps --status running -q app 2>/dev/null)" ]; then \
		$(DC) exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app \
			php artisan test --parallel; \
	else \
		echo "ℹ️  Container app tidak aktif; menjalankan test di host."; \
		DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --parallel; \
	fi

tinker: ## Laravel Tinker REPL
	$(DC) exec app php artisan tinker

##@ Frontend

npm-dev: ## Vite dev server (hot-reload) — jalankan di host, bukan di container
	npm run dev

npm-build: ## Build frontend assets di container
	$(DC) exec app npm run build 2>/dev/null || npm run build

##@ Git / GitHub

push: ## Commit semua + push ke branch aktif. Contoh: make push MSG="feat: add x"
	@bash scripts/git-push.sh $(MSG) $(BRANCH)

push-develop: ## Push ke develop
	@bash scripts/git-push.sh $(MSG) develop

push-main: ## Push ke main (release)
	@bash scripts/git-push.sh $(MSG) main

feature: ## Buat feature branch baru. Contoh: make feature NAME="feat/incident"
	@bash scripts/git-push.sh "feat: start $(NAME)" $(BRANCH) $(NAME)

status: ## git status
	@git status

log: ## git log --graph 10 commit terakhir
	@git log --oneline --graph --decorate -10

diff: ## git diff (perubahan belum di-commit)
	@git diff

##@ Misc

init: ## Setup pertama kali: cp .env.docker → .env, lalu docker compose up
	@[ -f .env ] && echo ".env sudah ada, skip copy." || cp .env.docker .env
	$(MAKE) up
	@echo ""
	@echo "🎉 Selesai! Buka: http://localhost:$$(grep APP_PORT .env | cut -d= -f2 || echo 8080)"

help: ## Tampilkan daftar perintah ini
	@echo ""
	@echo "  QHSSE App — Makefile"
	@echo "  Repo  : $(REPO)"
	@echo "  Branch: $(BRANCH)"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)
	@echo ""
