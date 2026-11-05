# CLAUDE.md — working notes for AI assistants and developers

CourtWatch is a court-monitoring platform for Kosovo. `PROJECT_PROPOSAL.md` is the
source of truth for scope; `docs/DECISIONS.md` records every choice made where the
spec was silent. Read both before changing behaviour.

## Stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Staff panel | Filament 5 at `/admin` (panel id `admin`) |
| Researcher area | Filament 5 at `/research` (panel id `research`) |
| Monitor PWA | Blade + Alpine at `/m` (offline drafts in IndexedDB) |
| Public portal | Laravel + Livewire 4 + Tailwind 4 at `/` (`/sq/...`, `/en/...`) |
| Database | PostgreSQL 16 with `pg_trgm` |
| Queue / cache / sessions | Redis + Laravel Horizon |
| Files | S3-compatible (RustFS in dev — MinIO-compatible; MinIO no longer publishes images), private, signed URLs |
| RBAC | spatie/laravel-permission + Filament Shield (role UI) + hand-written policies |
| Audit | spatie/laravel-activitylog |
| 2FA | Filament built-in app (TOTP) MFA, mandatory in `/admin` |
| API | Sanctum tokens, `/api/v1`, Scribe docs at `/docs/api` |
| Tests | Pest 4 (PostgreSQL), Playwright smoke tests in `e2e/` |
| Quality | Pint (laravel preset), Larastan level 6 |
| Design | Navy `#0c2a42`, yellow `#f8c33a`, green `#10937b`; Plus Jakarta Sans (self-hosted). Tokens: `resources/css/brand.css` (Tailwind) and `App\Support\Brand` (Filament, charts) |

## Commands

```bash
docker compose up                 # full dev stack, migrates + seeds automatically
composer lint                     # Pint --test
composer fix                      # Pint (fix)
composer analyse                  # Larastan
composer test                     # Pest (needs PostgreSQL; database courtwatch_test)
composer check                    # all three
php artisan app:seed-if-empty     # seed reference + demo data when the DB is empty
php artisan migrate:fresh --seed  # rebuild the dev database
php artisan app:create-admin you@example.org   # first Super Admin (production has no demo accounts)
php artisan legacy:inspect --connection=legacy  # compare the legacy schema with migration/config/legacy.php
php artisan legacy:import --connection=legacy   # legacy MySQL import (see docs/MIGRATION.md)
php artisan legacy:fixture        # fake legacy database (SQLite) for trying the importer
composer docs:api                 # regenerate the API docs (Scribe) after changing the API
npm run build                     # Vite build (public portal, monitor PWA, Filament theme)
npm run e2e                       # Playwright smoke tests (app must be running on :8000)
docker compose -f docker-compose.prod.yml up -d --build   # production stack (docs/DEPLOY.md)
```

## Layout

- `app/Enums` — all enumerations (case type, workflow status, stage types, hearing options…). Labels come from `lang/*/enums.php`.
- `app/Models` — `CourtCase` (table `cases`; `Case` is a PHP keyword), `CaseParty`, `Charge`, `CaseStage`, `Hearing`, `Measure`, `Sentence`, details tables, reference models under `app/Models/Reference`.
- `app/Policies` — server-side authorisation for every model. Never rely on hiding buttons.
- `app/Services` — `Analytics`, `Search`, `Export`, `Workflow`, `Anonymizer`, `Merge`, `CsvImport`.
- `app/Filament/Admin` — staff panel resources/pages/widgets; `app/Filament/Research` — researcher panel.
- `app/Http/Controllers/Api/V1` — researcher API (anonymized resources only).
- `app/Livewire/Portal` + `resources/views/portal` — public portal.
- `migration/` — legacy importer (`Migration\` namespace), config and fake fixture.
- `lang/sq`, `lang/en` — every user-facing string. `lang/sr` exists but is not translated yet.

## Rules (from the proposal — must always hold)

1. **Fake data only.** Never add real names of judges, prosecutors, monitors or accused persons. Seeders use Faker; only the public court structure and public criminal-code articles are real.
2. **Personal names are encrypted at rest** (`encrypted` casts) and **never** reach public routes, the researcher area or the API. `tests/Feature/Privacy/NoPersonalNamesTest.php` enforces this — keep it green and extend it when adding routes.
3. **Suppress public aggregate cells under 5** (`App\Services\Anonymizer::suppress`).
4. **Permissions are enforced with policies on the server** for every action. Monitors: create cases/hearings, edit own drafts and records under review, scoped to assigned courts, no delete. Analysts: read-only. Delete is admin-only and soft.
5. **Audit everything**: models use `LogsActivity`; logins, exports and permission changes are logged explicitly.
6. **Every user-facing string goes in translation files** (`__('...')`), Albanian default.
7. **Dates**: stored in UTC, displayed in `Europe/Belgrade` as `dd.mm.yyyy`.
8. **Workflow**: `draft → submitted → in_review → approved → published`, plus `returned` (with comment). Only approved/published data feeds dashboards, the public portal and researchers.
9. **Exports are always logged** in `export_logs` (admin, researcher and API).
10. **Never commit secrets, `.env` files, dumps, exports or real personal data.** `.env.example` has placeholders only.
11. Uploaded files are private, size/type-limited and served only through signed, expiring URLs.

## Conventions

- Conventional Commits (`feat(hearings): …`, `fix(cases): …`, `security(auth): …`), small and buildable.
- Branches: `feature/*`, `fix/*`; never push to `main` directly, never force-push.
- Run `composer check` before pushing. CI runs Pint, Larastan and Pest on PostgreSQL 16.
- Prefer the conventional Laravel/Filament way; log deviations in `docs/DECISIONS.md` and user-visible changes in `docs/CHANGELOG.md`.
