# Non-Functional Requirements

- **Performance**: list query via `ListQuery` + pagination; ekspor streaming.
- **Reliability**: queue worker systemd `qhsse-queue.service`; failover via retry.
- **Security**: RBAC + fail-closed; audit trail wajib.
- **Maintainability**: modular monolith; core reuse; DRY/KISS.
- **Testability**: Pest + SQLite in-memory; `make test` hijau.
- **Deployability**: `git pull` + `npm ci` + `npm run build` + `migrate --force` + restart php-fpm/queue.
