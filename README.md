# CourtWatch

Court-monitoring platform for Kosovo. Monitors attend hearings and record structured
observations on criminal, administrative, civil and economic cases; editors review them;
the verified data feeds staff dashboards, a public bilingual portal (without personal
names) and an API for approved researchers.

| Area | URL | Who |
| --- | --- | --- |
| Public portal (Albanian / English) | `/sq`, `/en` | Everyone |
| Staff panel | `/admin` | Super Admin, Editor, Monitor, Analyst (2FA mandatory) |
| Monitor app (installable, works offline) | `/m` | Monitors, on a phone |
| Researcher area | `/research` | Approved researchers |
| Researcher API and its OpenAPI docs | `/api/v1`, `/docs/api` | Approved researchers (tokens) |
| Queue dashboard | `/horizon` | Super Admins with 2FA |

The full specification is in [`PROJECT_PROPOSAL.md`](PROJECT_PROPOSAL.md); every choice made
where it was silent is in [`docs/DECISIONS.md`](docs/DECISIONS.md).

## Screenshots

_Screenshot placeholders — add images to `docs/screenshots/` and link them here:_

| Screen | File |
| --- | --- |
| Staff dashboard with analytics | `docs/screenshots/dashboard.png` |
| Case workspace (parties, stages, hearings) | `docs/screenshots/case.png` |
| Hearing form on a phone (monitor app) | `docs/screenshots/monitor-app.png` |
| Review queue | `docs/screenshots/review-queue.png` |
| Public portal home | `docs/screenshots/portal.png` |
| Researcher datasets | `docs/screenshots/research.png` |

## What is included

- **Cases and hearings**: one case record per court case with parties (names encrypted at
  rest), charges, a stage timeline from criminal report to the Supreme Court, measures,
  sentences and civil/administrative details; a short, phone-friendly hearing form pre-filled
  from the case, with autosave and an offline monitor app.
- **Review workflow**: draft → submitted → in review → approved → published, or returned with
  a comment; a review queue, a diff of changes and notifications. Only approved data is used
  for dashboards, the portal and research.
- **Reference data**: 16 lists (courts, judges, criminal offences…) with full CRUD,
  bilingual names, "merge duplicates" and CSV import.
- **Search and analytics**: global search with typo tolerance, saved and shared filters, bulk
  actions, logged CSV/XLSX exports and a dashboard with every indicator of the proposal.
- **Public portal**: statistics and charts by year, court and case type, anonymized case
  search, reports, methodology, open-data CSVs; every number under 5 is suppressed.
- **Researcher area and API**: registration, approval, data-use agreement, a chart builder, anonymized
  datasets and a read-only, rate-limited REST API; every export is logged.
- **Legacy migration toolkit**: `php artisan legacy:import` for the old MySQL database
  (idempotent, with a review queue and a reconciliation report) — see
  [`docs/MIGRATION.md`](docs/MIGRATION.md).
- **Security**: policies on every action, mandatory TOTP 2FA for staff, audit log of every
  change, login, export and permission change, security headers, sign-in limits, private
  files behind signed expiring links, encrypted daily off-site backups.

## Quick start with Docker

Requirements: Docker with Compose v2.

```bash
git clone https://github.com/is6998-crypto/Databaza-Monitorimi.git && cd Databaza-Monitorimi
```

```bash
docker compose up
```

The first start builds the development image, installs the dependencies, creates `.env`
with fresh keys, migrates the database and seeds the demo data (about 200 fake cases).
This takes a few minutes; the app is ready when the log says
`CourtWatch is ready on http://localhost:8000`.

| Service | Address |
| --- | --- |
| CourtWatch | <http://localhost:8000> (redirects to the portal) |
| Mailpit (all e-mails sent in development) | <http://localhost:8025> |
| S3 storage console (RustFS) | <http://localhost:9001/rustfs/console/> — `courtwatch` / `change-me-s3` |

## GitHub Codespaces

Open the repository on GitHub, choose **Code → Codespaces → Create codespace**. The dev
container starts the same stack, migrates and seeds automatically and forwards port 8000;
open the forwarded address when the "ready" message appears (the first start takes several
minutes; the address is also under **Ports → 8000**). Sign in with the demo accounts below.
GitHub serves the codespace over HTTPS through a proxy, which the dev container trusts
(`TRUSTED_PROXIES=*`), and it sets `APP_URL` to the codespace's address, from which the app
builds its links. If a link ever leads to `localhost`, put the forwarded address (from
**Ports → 8000**) in `APP_URL` in `.env`.

## Demo logins (development only)

All demo accounts use the password **`password`**. They are created only outside production
(`SEED_DEMO_DATA=true`); a production installation starts without them.

| Role | E-mail | What to try |
| --- | --- | --- |
| Super Admin | `admin@courtwatch.test` | Users and roles, researcher approvals, audit log, legacy review |
| Editor (program manager) | `editor@courtwatch.test` | Review queue: approve or return; publications; reference data |
| Monitor | `monitor@courtwatch.test` | Courts of Prishtina and Prizren: new case, hearing form, `/m` on a phone |
| Monitor | `monitor2@courtwatch.test` | Court of Gjilan (sees only its own courts) |
| Analyst | `analyst@courtwatch.test` | Read-only dashboard, lists and exports |
| Researcher (approved) | `researcher@courtwatch.test` | `/research`: chart builder, datasets, API tokens, `/docs/api` |
| Researcher (pending) | `researcher.pending@courtwatch.test` | Waiting for approval — approve it as the admin |

> [!NOTE]
> **Staff must set up two-factor authentication at the first sign-in.** Use any authenticator
> app (Aegis, Google Authenticator, 1Password…): scan the QR code or type the code shown,
> confirm with a 6-digit code and keep the recovery codes. Researchers may enable 2FA from
> their profile. An administrator can reset a user's 2FA in **Administration → Users**.

### Try the main flow

1. Sign in as `monitor@courtwatch.test` on a phone-sized window, open **Cases → New case**,
   add the parties and charges, then record a hearing (or use `/m`, which also works offline)
   and **send it for review**.
2. Sign in as `editor@courtwatch.test`, open the **Review queue** and approve the case and the
   hearing (or return them with a comment).
3. The dashboard now counts them, and the public portal shows the case anonymized (public code
   `CW-…`, no names, no case numbers).

## Without Docker

Requirements: PHP 8.3 (`pdo_pgsql`, `intl`, `zip`, `gd`, `bcmath`, `exif`, `pcntl`, `redis`),
Composer 2, Node.js 22, PostgreSQL 16 and Redis 7.

```bash
composer install && cp .env.example .env && php artisan key:generate
```

Edit `.env`: database credentials, `BLIND_INDEX_KEY` (64 hex characters, e.g. from
`php -r "echo bin2hex(random_bytes(32));"`), `SEED_DEMO_DATA=true`, and
`FILESYSTEM_DISK=local` / `ATTACHMENTS_DISK=local` if you run no S3-compatible storage. Then:

```bash
php artisan migrate --seed && npm ci && npm run build
```

```bash
php artisan serve
```

Run `php artisan horizon` in a second terminal for queued e-mails and notifications.

## Development

| Command | What it does |
| --- | --- |
| `composer lint` / `composer fix` | Check / fix code style (Pint, Laravel preset) |
| `composer analyse` | Static analysis (Larastan, level 6) |
| `composer test` | Pest test suite (PostgreSQL database `courtwatch_test`) |
| `composer check` | All three, as in CI |
| `npm run dev` / `npm run build` | Vite (portal, monitor app, Filament theme) |
| `npm run e2e` | Playwright smoke tests against a running app with demo data (see `playwright.config.ts`) |
| `composer docs:api` | Regenerate the API docs after changing the API |
| `php artisan app:create-admin` | Create a Super Admin (first production account) |
| `php artisan legacy:inspect` / `legacy:import` / `legacy:fixture` | Legacy migration toolkit |
| `php artisan migrate:fresh --seed` | Rebuild the development database |

The test suite covers policies and the role matrix, validation rules, the review workflow,
anonymization and small-cell suppression, an automatic crawl proving that no public page,
researcher page, download or API response contains a personal name, the API, the hardening
and the legacy importer (against a fake legacy database). CI (GitHub Actions) runs Pint,
Larastan and Pest on PostgreSQL 16 for every push and pull request.

The first `npm run e2e` run goes through the real 2FA set-up for the demo staff accounts and
keeps their secrets in `e2e/.auth/` (git-ignored) for the next runs.

## Project layout

```text
app/
  Enums/                 case types, stages, workflow statuses, hearing options…
  Models/                CourtCase (table "cases"), CaseParty, Hearing, Reference/*…
  Policies/              server-side authorisation for every model
  Services/              Analytics, Search, Export, Workflow, Anonymizer, Research…
  Filament/Admin/        staff panel; Filament/Research/: researcher area
  Http/Controllers/Api/  researcher API (/api/v1)
  Livewire/Portal/       public portal components
migration/               legacy importer (namespace Migration\) and its mapping
lang/sq, lang/en         every user-facing string (Albanian is the default)
docker/, Dockerfile      development and production images
docs/                    decisions, changelog, deployment, migration, user guides
e2e/                     Playwright smoke tests
```

## Documentation

- [Staff user guide (Shqip)](docs/user-guide-sq.md) · [Staff user guide (English)](docs/user-guide-en.md)
- [Deployment](docs/DEPLOY.md) — VPS, Docker Compose, automatic HTTPS, backups and restore
- [Legacy migration](docs/MIGRATION.md)
- [Decisions](docs/DECISIONS.md) · [Changelog](docs/CHANGELOG.md)
- [`CLAUDE.md`](CLAUDE.md) — working notes and the rules every change must respect
- API reference: `/docs/api` on any running instance

## Data protection

CourtWatch processes personal data under Kosovo Law No. 06/L-082 on Protection of Personal
Data (with the GDPR as the reference). Personal names are encrypted at rest and never leave
the staff panel; public and research outputs are anonymized and logged. **Use fake data
only** in development and tests, and never commit `.env` files, database dumps, exports or
real personal data. Report security problems privately to the maintainers, not in public
issues.
