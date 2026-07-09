# =============================================================================
# Makefile — QHSSE App Developer Commands
# =============================================================================
# Usage: make <target>
# Contoh: make push MSG="feat: incident reporting"
#         make push-main MSG="release: Phase 1"
#         make feature NAME="feat/incident-reporting"
# =============================================================================

.PHONY: push push-main push-develop feature status log help
.DEFAULT_GOAL := help

REPO   := https://github.com/topiksarip/qhsse-app
BRANCH := $(shell git branch --show-current 2>/dev/null || echo "main")
MSG    ?= "chore: update $(shell date '+%Y-%m-%d %H:%M')"

## ──────────────────────────────────────────────────────────────────────────────
## Git / GitHub
## ──────────────────────────────────────────────────────────────────────────────

push: ## Push ke branch aktif saat ini. Contoh: make push MSG="feat: add login"
	@bash scripts/git-push.sh $(MSG) $(BRANCH)

push-develop: ## Push ke branch develop
	@bash scripts/git-push.sh $(MSG) develop

push-main: ## Push ke branch main (release)
	@bash scripts/git-push.sh $(MSG) main

feature: ## Buat dan push feature branch baru. Contoh: make feature NAME="feat/incident"
	@bash scripts/git-push.sh "feat: start $(NAME)" $(BRANCH) $(NAME)

status: ## Lihat git status
	@git status

log: ## Lihat 10 commit terakhir
	@git log --oneline --graph --decorate -10

diff: ## Lihat perubahan yang belum di-commit
	@git diff

## ──────────────────────────────────────────────────────────────────────────────
## Laravel (via Docker — akan dipakai setelah Docker setup)
## ──────────────────────────────────────────────────────────────────────────────

artisan: ## Jalankan artisan command. Contoh: make artisan CMD="route:list"
	@docker compose exec app php artisan $(CMD)

migrate: ## php artisan migrate --seed
	@docker compose exec app php artisan migrate --seed

test: ## Jalankan test suite
	@docker compose exec app php artisan test

build: ## npm run build (frontend)
	@docker compose exec node npm run build

## ──────────────────────────────────────────────────────────────────────────────
## Docker
## ──────────────────────────────────────────────────────────────────────────────

up: ## docker compose up -d
	@docker compose up -d

down: ## docker compose down
	@docker compose down

restart: ## Restart semua container
	@docker compose restart

logs: ## Lihat logs semua container
	@docker compose logs -f

shell: ## Masuk ke shell container app
	@docker compose exec app bash

## ──────────────────────────────────────────────────────────────────────────────

help: ## Tampilkan daftar perintah ini
	@echo ""
	@echo "  QHSSE App — Makefile Commands"
	@echo "  Repo: $(REPO)"
	@echo "  Branch aktif: $(BRANCH)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'
	@echo ""
