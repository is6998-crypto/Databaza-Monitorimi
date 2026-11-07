# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Conventional Commits](https://www.conventionalcommits.org/).

## [Unreleased] — v1 build

### Added
- Project scaffold: Laravel 13, Filament 5, Livewire 4, Tailwind 4, Pest, Larastan, Pint.
- Docker Compose dev stack (app, Horizon, scheduler, PostgreSQL 16, Redis, MinIO, Mailpit) and a Codespaces dev container that installs, migrates, seeds and serves automatically.
- GitHub Actions CI: Pint, Larastan and Pest on PostgreSQL 16; Dependabot for Composer, npm and Actions.
- Staff panel at `/admin` with login, password reset, profile, mandatory TOTP 2FA (with recovery codes) and a language switcher (Albanian/English).
- 30-minute idle timeout with a warning modal and a "Stay signed in" button (shared across tabs).
- Roles (Super Admin, Editor, Monitor, Analyst, Researcher) and permissions, Shield role editor, user management with monitor court scope and 2FA reset.
- Audit-log viewer; logins, failed logins, logouts, password resets, role/permission changes and model changes are logged.
- Full database schema for reference data, cases, parties, charges, stages, hearings, measures, sentences, administrative/civil details, workflow history, attachments, saved filters, publications, export logs, researcher profiles and legacy-import bookkeeping.
- Reference data: 16 tables (courts, branches, departments, divisions, prosecution offices, judges, prosecutors, monitors, applicants, criminal codes, criminal offences, categories, nationalities, defendant positions, economic statuses, security measure types) with CRUD, search, bilingual names, soft delete/restore, merge duplicates and CSV import with templates.
- Seeded public structure: the 7 basic courts and their branches, Court of Appeals, Supreme Court, court departments and divisions, prosecution offices, KPRK 2019 / KPK 2012 / UNMIK 2003/25 with a sample of articles; fake judges and prosecutors in development.
- Cases: list with faceted filters, create/edit with duplicate-number warning and administrative/civil details, and a case workspace with tabs for overview, parties & charges, stages timeline (chronology validation), hearings, measures & sentences, documents and history.
- Hearings: phone-friendly form pre-filled from the case, conditional follow-up questions, hearing date not in the future, autosaved drafts and "recently used" values first in selects.
- Monitor app (`/m`): installable PWA with offline drafts in IndexedDB, an outbox that syncs when back online, and "send for review".
- Private attachments with signed, expiring download links.
- Demo data: about 200 fake cases with parties, charges, stage timelines, hearings, measures, sentences and review history.
- Review workflow for cases and hearings: submit for review, start review, approve, return with a mandatory comment, publish/unpublish; a diff of changes since the last reviewed version; review queue with a badge; database and email notifications; full transition history on each record.
- Search: global search (Ctrl/Cmd+K) and list search across case/indictment/police numbers (with typo tolerance via pg_trgm), judges, offences and party names (blind index).
- Saved filters (private or shared) on the case and hearing lists; filters and search are kept in the URL.
- Bulk actions: assign monitor, change status (through the review workflow), export selected, soft delete (Super Admin only); CSV/XLSX export of filtered lists; export log screen. Every export is logged.
- Analytics dashboard filterable by date range, court and case type: headline stats, hearings over time, postponement rate (by court/judge/case type) and reasons, punctuality and delay causes, public vs closed, recording and interpretation compliance, median days between stages, indictment outcomes, conviction vs acquittal, sentence distribution, plea agreements, appeal outcomes, seizure and confiscation requested vs granted. Monitors see their drafts and returned hearings instead.
- Public portal at `/sq` and `/en`: home with headline statistics and interactive charts by year, court and case type (Apache ECharts), anonymized case search and case pages, reports & publications, methodology, about and data-protection pages, open-data CSV downloads, language switcher, SEO meta tags, `hreflang` and a sitemap. Every aggregate under 5 is suppressed; no personal names are ever shown.
- Publications management for editors.
- Automated privacy test that crawls every public route and component and fails if a personal name or case number appears.
- Researcher area at `/research`: self-registration with institution and research purpose, approval status page, versioned data-use agreement, anonymized dataset downloads (cases, parties, hearings; CSV/XLSX) and personal API tokens. Every download is logged.
- "Researchers" screen in the staff panel to approve, reject or suspend researcher accounts, with e-mail notifications; suspension revokes all tokens.
- Read-only researcher API at `/api/v1` (cases, parties, hearings, aggregate statistics, reference lists) with Sanctum tokens, rate limiting, filters, pagination, Albanian/English labels and logging of every request; OpenAPI docs at `/docs/api`.
- The privacy test now also crawls every researcher page, every API endpoint, the research downloads and the API docs.
- Legacy migration toolkit (`migration/`): `php artisan legacy:import --connection=legacy` reads the old MySQL database read-only, groups the flat rows into cases, removes duplicates from reference data, maps free text to codes, sends everything it cannot map to `legacy_review_items` (encrypted) and writes a reconciliation report; idempotent and re-runnable, with `--dry-run`, `--module` and `--limit`. `legacy:inspect` compares the mapping with the legacy schema; `legacy:fixture` builds a fake legacy database for trials.
- "Legacy review" screen in the staff panel to work through the import's review items (filters, decrypted details, link to the case, resolve/reopen).
- `docs/MIGRATION.md`: how to run the migration safely on the server or a secure computer.
- Security headers on every response (CSP, HSTS over HTTPS, X-Frame-Options, Referrer-Policy, Permissions-Policy), an account lock after repeated failed sign-ins (on top of the per-IP limit), and bilingual error pages without technical details.
- Health check `/up` now verifies the database and the cache; `/horizon` is limited to Super Admins with 2FA.
- Daily encrypted backups (database + private files) to an off-site S3-compatible disk, with cleanup and monitoring.
- Production deployment: `Dockerfile` (FrankenPHP with automatic HTTPS), `docker-compose.prod.yml`, Caddyfile and entrypoint, `php artisan app:create-admin`, and `docs/DEPLOY.md` (VPS setup, backups and restore, updates, monitoring, security checklist).
- Playwright smoke tests (`npm run e2e`): portal in both languages, staff sign-in through the real 2FA set-up, dashboard, review queue, monitor app on a phone-sized screen, approved and pending researchers.
- A GitHub Actions workflow that runs `docker compose up` on a clean machine and builds and starts the production stack over HTTPS.
- Full README (setup with Docker, Codespaces or locally, demo logins, commands) and staff user guides in Albanian and English.

### Changed
- Page titles in the staff panel are no longer Title Cased, so Albanian labels read correctly.
- The API documentation loads its libraries from this server instead of public CDNs.
- Development storage: MinIO (whose Docker images are no longer published) is replaced by RustFS, an S3-compatible server with the same API and ports; `php artisan app:ensure-bucket` creates the private bucket.
- New visual design across the whole platform, following the owner's mockups (texts, fields and flows are unchanged): only navy and yellow on white and a light grey background (no red, orange or green anywhere), the Plus Jakarta Sans font (served from this site) and a new logo, favicon and app icon.
  - Staff panel and researcher area: a navy sidebar with the logo at the top and the active page in yellow, the search on the left of the top bar, larger bold headings, clean white cards and tables, one yellow main button per screen (editing is a white button, workflow steps are navy), navy switches, and status badges told apart by style: solid navy (approved), solid yellow (published), yellow tint (in review), navy tint (submitted, case types), navy outline (returned), grey (draft). Errors are shown in bold navy. Dark mode follows the same colours.
  - Public portal: navy header and footer, a navy introduction band on the home page, the headline numbers in one card with icons, charts in navy, yellow and grey (the latest year in yellow), filters in a side panel next to the case results, a navy step timeline on case pages and covers on report cards.
  - Monitor app: navy header, a yellow "new hearing" button, chosen answers in yellow with a check mark, and draft states told apart by style (draft yellow tint, waiting light navy, sent solid navy, error solid yellow).
  - The API documentation, error pages and the idle-timeout warning use the same two colours.
- Public portal design pass (same colours, texts and pages):
  - Every inner page (case search, case page, reports, open data, methodology, about, data protection) opens with the same navy band as the home page; the header stays at the top while scrolling.
  - Home: the filters and the six headline numbers share one panel over the introduction band; the introduction arrives once on load (not under reduced motion).
  - Charts: values printed at the end of each bar; doughnut charts no longer cut their labels off (the legend wraps underneath, the share is in the tooltip); axis text at 13px.
  - Case and report cards are one link each, lift slightly on hover and show the case reference above the court; report covers show the type instead of repeating the logo.
  - Labels in sentence case instead of spaced capitals, reading text at 15–16px, buttons and controls at least 44px high, a full-width phone menu with a close icon, pagination with the count above the page buttons.
- Staff panel, researcher area, monitor app and error pages design pass (same colours and texts):
  - Panels: sidebar group names and table column headings in sentence case; on detail pages a small grey label over a navy value; the active tab is a navy tint with a yellow bar; buttons step up on hover and press on click with a yellow focus ring; sign-in and two-factor pages sit on a navy band.
  - Workflow buttons are all navy ("Start review" and "Publish" were yellow), so each screen keeps one yellow main button.
  - Dashboard: an icon on every headline figure, the public/closed chart is a doughnut like the others, the timeline's month labels are straight and thinned out.
  - Monitor app: the navy header runs the full window width, section headings in sentence case, larger tap targets and text (13px minimum), a press response on buttons, an arrow on recently sent rows.
  - Error pages: the navy band and a yellow "Go to the home page" button.
- Second design pass with the design-taste-frontend skill (same colours, texts and functions):
  - Home: the first screen is a split hero: the question and the two actions at the left, the real case search at the right (the three latest verified cases, anonymized as everywhere on the portal) with a live "Last update" month. The statistics have a visible heading; chart titles are headings above the charts.
  - Home: a "Methodology" section walks through its four steps (monitoring, verification, indicators, privacy) beside a drawing of the matching part of the platform; on a large screen the section holds while the steps open one at a time (the skill's pinned-chapters moment, served from `public/assets/moments`); on phones and under reduced motion every step stands with its picture.
  - Home: the page closes with the tagline set large and "Search monitored cases" above the footer links; "All reports" is a button under the latest reports.
  - Every public section sits on one container (1280px, the gutter as margin); the language switch is a quiet navy toggle; focus outlines are navy on light grounds and yellow on navy.
  - Staff and researcher sign-in, two-factor and password pages: a navy brand panel at the left (logo, tagline, the privacy promise or the researchers' first rule) and the form at the right; the page heading arrives once on load.
- New logo mark: a thick yellow square frame with its top-left and bottom-right corners cut on the diagonal (one drawing in `resources/views/partials/logo-mark.blade.php`, used by the portal, both panels, the monitor app and the error pages); new favicon and app icons.
- Charts redrawn (portal and staff dashboard): one colour (navy) with yellow only for the latest year; the doughnuts are now ranked horizontal bars (largest first); the portal's comparisons are HTML bar lists with the value at each bar's end and nothing clipped; thin bars with rounded ends, hairline grids, white tooltips; the staff dashboard's slanted labels are gone (horizontal bars). Fixed: counts in the portal charts' data tables no longer show a stray "%".

### Fixed
- In GitHub Codespaces the home page could send the browser to `localhost:8000` (the visitor's own computer), because the app saw the forwarded request as coming to localhost. In development, links are now built from `APP_URL` whenever it is an https address (the dev container sets it to the Codespace's address).
- `TRUSTED_PROXIES` in `.env` was ignored (it was read before `.env` is loaded), so behind a proxy such as GitHub Codespaces the pages linked their styles and scripts over http:// and the browser blocked them. The setting is now read on every request (`config/trustedproxy.php`), and the dev container trusts the Codespaces proxy.
- The introduction of the API documentation was shown as raw Markdown in a code box (indented text in `config/scribe.php`).
- `composer docs:api` deleted the self-hosted libraries of the API documentation (Scribe empties `public/vendor/scribe`); they now live in `public/docs-assets`.
- `docker compose up` failed on a clean machine because the `minio/minio` and `minio/mc` images can no longer be pulled.
- The production stack crashed at start when `BACKUP_NOTIFICATION_EMAIL` was left empty (the backup package rejects an empty recipient); it now falls back to the sender address.
