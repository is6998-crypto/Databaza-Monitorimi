# Project Proposal & Build Spec: Court Monitoring Platform

> **Working name:** CourtWatch (placeholder, rename freely)
>
> **This file is both the project proposal and the build instructions for Claude Code.** It lives in the root of the GitHub repository. Claude Code, which runs in the cloud from claude.ai/code and is connected to this repo, reads it and **builds the complete, working platform in one go**. It commits to a branch and opens a pull request for the owner to review and merge on GitHub.

---

## Part A: For the project owner (how to run this through GitHub)

### A1. One-time setup (≈10 minutes, all in the browser)

1. **Create the repo on GitHub:**
   - Private visibility.
   - **No** README, **no** .gitignore and **no** license. Claude Code creates them.
2. **Add this file:**
   - In the empty repo, click **"uploading an existing file"**, drag in `PROJECT_PROPOSAL.md` and click **Commit changes** to `main`.
3. **Connect Claude Code:**
   - Go to **claude.ai/code** and connect your GitHub account when asked.
   - Give the Claude GitHub app access to **this repo only**.
4. **Start the build:**
   - Pick the repo, choose the default cloud environment, paste the prompt from **A2** and send it.
5. Claude Code works on its own in a cloud sandbox. You can watch progress or close the tab and come back later.
6. When it finishes, it opens a **pull request**:
   - Review it on GitHub (Files changed), leave comments, and ask Claude Code for fixes in the same session.
   - Click **Squash and merge** when you're happy.
7. **See it running, with no install:**
   - In the repo, click **Code → Codespaces → Create codespace on main**.
   - The app starts automatically with fake demo data. Open the forwarded port and sign in with the demo accounts listed in the README.

### A2. The prompt to paste into Claude Code

```
Read PROJECT_PROPOSAL.md in full and build the complete platform it describes,
following Part B (Build Instructions) exactly. Work autonomously from start to
finish without stopping to ask, unless you are truly blocked. Make sensible
choices where details are missing and record them in docs/DECISIONS.md.
Work on the branch build/v1, commit often with Conventional Commit messages,
push regularly, and when everything in the Definition of Done (B9) passes,
open a pull request into main with a summary, setup steps, demo logins and
a list of anything left unfinished.
```

### A3. After the first build

- Request each change or new feature in a **new Claude Code session** (or the same one), one topic at a time. Each one ends as its own pull request.
- You can also open a **GitHub Issue** describing a bug or feature and paste its link into Claude Code.
- **Never** upload the old database dump or any file with real names to GitHub. The legacy migration (B8) runs only on the production server or a secure computer, never in the repo.

### A4. Repository settings to switch on after the first merge

- **Settings → Rules → Rulesets** for `main`:
  - Require a pull request before merging
  - Require status checks to pass (the CI workflow)
  - Block force pushes and deletions
- **Settings → General:** allow **Squash merging** only, and turn on "Automatically delete head branches".
- **Settings → Code security:** turn on Dependabot alerts and security updates, plus secret scanning if your plan offers it.
- Require two-factor authentication on your GitHub account.

---

## Part B: Build instructions for Claude Code

### B0. Mode of work

- Build the **whole platform in this run**:
  - All modules
  - The staff panel, monitor workflow, analytics, public portal and researcher API
  - Seed data, tests, CI and the dev environment
- Do **not** stop after a phase. Use the build order in B6, and never leave a step half-wired.
- Breadth first: every module must work end to end, with real CRUD, validation and permissions, before you polish any one module.
- Where this spec is silent, choose the conventional Laravel/Filament way and log it in `docs/DECISIONS.md`.
- Run tests and linters before each push. Commit at least once per completed step (B6).
- If PostgreSQL isn't available in your sandbox, install it (`apt-get install postgresql`) or use SQLite for local test runs only. CI must run against PostgreSQL.
- **Use fake data only.** Generate realistic Kosovo-context fake data with Faker. Never invent or include real names of judges, prosecutors, monitors or accused persons.

### B1. Background

The organization runs a court-monitoring program in Kosovo. Monitors attend court hearings and record structured observations about criminal, administrative, civil and economic cases. The data is collected across prosecution, investigation, indictment, trial, judgment, appeal and Supreme Court stages. The data supports advocacy, public reports and research.

The legacy system (a Laravel 5-era admin panel) holds:

| Module | Legacy records |
|---|---|
| Criminal – Corruption | ~28,700 |
| Criminal – General Department | ~15,400 |
| Criminal – Serious Crimes | ~13,400 |
| Criminal – Special Department | ~7,100 |
| Civil | ~5,600 |
| Administrative Department | ~630 |
| Economic Department | ~50 |

**Legacy problems this build must fix:**

1. Data entry is slow:
   - The criminal hearing form has ~580 inputs on one page.
   - Case data is re-entered for every hearing.
   - A hard session-lock timer logs users out mid-entry.
2. Search is weak:
   - Filtering is a wall of dropdowns.
   - The only bulk action is Delete.
   - There are no dashboards, and the Reports section is unused.
3. Security is weak:
   - Debug stack traces are exposed in production.
   - Some edit pages crash.
   - Reference lists contain duplicates.
   - There's no audit trail or review workflow.
4. The UI is outdated and desktop-only, and only partly translated.

### B2. Tech stack (fixed, all free and open source)

| Layer | Choice |
|---|---|
| Backend | PHP 8.3+, **Laravel** (latest stable) |
| Staff panel | **Filament** (latest stable), at `/admin` |
| Public portal | Laravel + Livewire + Tailwind CSS (server-rendered, SEO-friendly), at `/` |
| Database | **PostgreSQL 16+** (with `pg_trgm` for fuzzy search) |
| Queue/cache | Redis + Laravel Horizon |
| Files | S3-compatible storage (MinIO in dev) |
| Charts | Apache ECharts (or Filament chart widgets) |
| RBAC | spatie/laravel-permission (+ Filament Shield) |
| Audit log | spatie/laravel-activitylog |
| 2FA | TOTP (Filament's built-in MFA, or a maintained plugin) |
| API | Laravel Sanctum tokens, OpenAPI docs (e.g., Scribe) |
| Tests | Pest; Playwright or Dusk for a few E2E smoke tests |
| Quality | Laravel Pint, Larastan (level ≥ 6) |
| Dev env | **Docker Compose** + **`.devcontainer/`** for GitHub Codespaces (auto-migrate and seed on create, forward the app port) |
| CI | **GitHub Actions**: lint, static analysis, tests on PostgreSQL |

### B3. Languages

- Albanian (default) and English, with a language switcher.
- Serbian-ready: every user-facing string goes in translation files, and reference data has `name_sq`, `name_en` and `name_sr` (nullable).
- Store dates in UTC and display them in `Europe/Belgrade` (Kosovo time) in the format `dd.mm.yyyy`.

### B4. Roles and permissions

| Role | Permissions |
|---|---|
| Super Admin | Everything: users, roles, settings, reference data, hard actions |
| Editor (Program Manager) | Review, approve or return submissions; edit any case; manage reference data; publish reports |
| Monitor | Create cases and hearings; edit own drafts and records under review; sees cases in their assigned courts; no delete |
| Analyst | Read-only access to all internal data, dashboards and exports |
| Researcher | Approved external account; anonymized data only, via the researcher area and API; every export logged |
| Public | Anonymous visitors to the public portal |

Requirements:

- Enforce permissions with Laravel policies on the server for every action.
- 2FA is **mandatory** for all staff roles.
- Monitors can be scoped to specific courts.

### B5. Domain model

**Reference data:**

- Admin-managed and soft-deletable, with bilingual names and an `is_active` flag.
- Each table has a **"merge duplicates"** action that re-points all references.
- Bulk CSV import.

Reference tables:

- `courts` (with `level`: basic, appeal, supreme), `court_branches`, `departments`, `divisions`, `prosecution_offices`
- `judges`, `prosecutors`, `monitors` (each monitor is linked to a `users` row), `applicants` (who filed the criminal report)
- `criminal_codes` (KPRK 2019, KPK, UNMIK Regulation) and `criminal_offences` (code, article number, title sq/en/sr, chapter, category)
- `categories`, `nationalities`, `defendant_positions`, `economic_statuses`, `security_measure_types`

**Core entities:**

- **`cases`**
  - Type (enum): `criminal_corruption`, `criminal_serious`, `criminal_general`, `criminal_special`, `administrative`, `civil`, `economic`.
  - Court, branch, department, division, presiding judge and case prosecutor.
  - Court case number, indictment number, police case number.
  - Workflow state.
- **`case_parties`**
  - Role (accused, legal entity, plaintiff, respondent, injured party).
  - Gender (M/F/legal person/other), nationality, economic status, position held.
  - Counsel type (retained or ex officio), and whether counsel was present at police questioning.
  - **Names are encrypted at rest** (Laravel encrypted casts) and never shown on the public portal.
- **`charges`**: party × offence × stage (criminal report, indictment, judgment).
- **`case_stages`**: one row per milestone, each with a date and an outcome:
  - Criminal report filed (applicant)
  - Investigation opened, closed or suspended
  - Indictment drafted (by which prosecutor) and filed; direct indictment flag
  - Initial, second and main hearings
  - Indictment decision (approved, rejected or dismissed)
  - Judgment announced and drafted (convicting or acquitting); plea agreement
  - Appeal (by whom) and appeal decision
  - Request for protection of legality, Supreme Court decision
  - Judgment executed
- **`hearings`**: one row per monitored session.
  - Date and monitor.
  - Venue: office or courtroom.
  - Public or closed.
  - Started on time or more than 15 minutes late, plus the cause of delay (panel, prosecutor, defence, other).
  - Postponed, plus postponement reasons (multi-select: prosecutor, judge, defence lawyer, accused, injured party, expert, other).
  - Phone use and by whom (multi-select).
  - Security adequate, plus a comment.
  - Recorded (audio/video).
  - Interpretation needed and provided.
  - Participants present (multi-select).
  - Notes.
- **`measures`**: security measures; seizure and confiscation requested and granted, with descriptions.
- **`sentences`**:
  - Per party: effective prison (months), suspended sentence (months), fine (EUR) and supplementary punishment.
  - Store both the requested and the imposed sentence.
- **Administrative and civil cases** use the same `cases`, `case_parties`, `case_stages` and `hearings` tables. Type-specific fields go in their own tables (`administrative_case_details`, `civil_case_details`), not in JSON. The administrative fields are:
  - First-instance decision and its date; whether it was reasoned; date the party received it
  - Legal remedy against the decision or administrative silence
  - Second-instance decision and its date
  - Administrative conflict filed in court; plaintiff type (natural/legal person, NGO, Ombudsperson); respondent body; representation
  - Review dates; participants in the hearing

**Cross-cutting:**

- `attachments` (PDFs, private, served through signed URLs).
- Activity/audit log on every model.
- Workflow: `draft → submitted → in_review → approved → published`, plus `returned` with a comment.
- `saved_filters`, `publications`, `export_logs`, API tokens.

**Validation:**

- Stage dates must be chronological.
- A hearing date can't be in the future.
- Sentence values must be ≥ 0.
- Warn on duplicate case numbers in the same court, and offer to open the existing case.

### B6. Build order (complete every step in this run)

1. **Repository setup:**
   - Laravel + Filament + Livewire + Tailwind installed.
   - `README.md`, `CLAUDE.md` (stack, commands, conventions, rules from this file), `docs/DECISIONS.md`, `docs/CHANGELOG.md`.
   - `.gitignore` (standard Laravel, **plus** `.env*` except `.env.example`, `*.sql`, `*.sql.gz`, `*.dump`, `*.bak`, `/migration/data/`, `/backups/`, `/exports/`, `.claude/settings.local.json`).
   - `.gitattributes` (`* text=auto eol=lf`).
   - `.github/pull_request_template.md`, `.github/dependabot.yml` (composer, npm, actions).
   - `.github/workflows/ci.yml` (Pint, Larastan and Pest on PostgreSQL 16).
   - `docker-compose.yml` (app, postgres, redis, minio, mailpit).
   - `.devcontainer/devcontainer.json` (Codespaces: install, migrate, seed and serve automatically).
2. **Auth and admin foundation:**
   - Login, mandatory TOTP 2FA for staff, password reset.
   - **Idle timeout of 30 minutes with a warning modal and an "extend" button.** Never a hard lock.
   - Roles and permissions, user management, audit-log viewer.
3. **Reference data:** all tables in B5 with Filament resources, search, bilingual names, merge-duplicates and CSV import. Seed realistic **fake** data, but use the real public structure where it's public knowledge:
   - The 7 basic courts and their branches, the Court of Appeals and the Supreme Court.
   - A sample of real criminal-code article numbers and titles.
4. **Cases and hearings (core):**
   - A **case workspace page** with tabs: Overview · Parties & Charges · Stages timeline · Hearings · Measures & Sentences · Documents · History.
   - An **"Add hearing"** action that pre-fills court, judge, prosecutor and parties from the case. It asks only hearing-specific questions, and shows conditional fields only when relevant.
   - Autosave of drafts.
   - Searchable selects that show recently used values first.
   - A mobile-first layout for monitors. Make the monitor experience an installable **PWA**, with offline drafts stored in IndexedDB and synced when back online.
5. **Review workflow:** a submit-for-review step, an editor review queue, approve or return with a comment, a diff of changes, and notifications (database plus email via Mailpit in dev).
6. **Search and analytics:**
   - Global search (case number, indictment number, party, judge, offence) using `pg_trgm`.
   - Faceted filters with URL state, and saved filters.
   - Bulk actions: assign monitor, change status, export. Delete is admin-only and soft.
   - CSV/XLSX export, with every export logged.
   - A dashboard of widgets, filterable by date range, court and case type:
     - Hearings over time
     - Postponement rate and reasons by court, judge and case type
     - Punctuality and delay causes
     - Public vs. closed hearings
     - Recording and interpretation compliance
     - Median days between stages
     - Indictment outcomes; conviction vs. acquittal
     - Sentence distribution, plea agreements, appeal outcomes
     - Seizure and confiscation requested vs. granted
7. **Public portal (`/`):**
   - Pages: Home with headline statistics, interactive charts by year, court and case type, Reports & Publications, Methodology, About, Data Protection.
   - Anonymized case search.
   - Open-data CSV downloads of aggregate tables.
   - **Never expose personal names.** Suppress any aggregate cell under 5.
   - Sq/En switcher, SEO meta tags and a sitemap.
8. **Researcher area and API:**
   - Researchers register, an admin approves them, and they accept a data-use agreement.
   - Anonymized record-level exports.
   - A read-only REST API (`/api/v1`) with Sanctum tokens, rate limiting and OpenAPI docs at `/docs/api`.
9. **Legacy migration toolkit** (code only, see B8).
10. **Hardening:**
    - Security headers (CSP, HSTS, X-Frame-Options, Referrer-Policy) and login rate limiting.
    - `APP_DEBUG=false` in the production example env, plus custom error pages.
    - Backup command (spatie/laravel-backup) and a health-check endpoint `/up`.
    - A production `Dockerfile` and `docs/DEPLOY.md` (VPS + Docker Compose + Caddy auto-TLS + daily encrypted off-site backups + restore steps).
11. **Docs and demo:**
    - README with screenshots placeholders, setup (local Docker and Codespaces), and demo logins for each role (seeded, dev only).
    - A staff user guide in Albanian and English (`docs/user-guide-sq.md`, `docs/user-guide-en.md`).

### B7. Security and privacy (must hold in every step)

- Comply with Kosovo Law No. 06/L-082 on Protection of Personal Data, using GDPR as the reference.
- Encrypt personal names at rest. Use TLS only in production.
- Add an automated test proving that **no public or researcher route or API response contains personal-name fields**.
- Audit every create, update, delete, login, export and permission change.
- Never commit secrets. `.env.example` holds placeholders only.
- Uploaded files are private, size- and type-limited, and served only through signed, expiring URLs.

### B8. Legacy migration (build the tool; do not run it on real data)

- Create `migration/` with an idempotent, re-runnable import command: `php artisan legacy:import --connection=legacy`.
- It reads the old MySQL database through a configurable second DB connection (`LEGACY_DB_*` env vars).
- It must:
  - De-duplicate reference data.
  - Split the legacy flat rows (one per hearing/defendant) into cases, parties, stages and hearings.
  - Map free text to enums where possible.
  - Send anything it can't map to a `legacy_review_items` table instead of dropping it.
- It produces a reconciliation report (counts per legacy module vs. new tables).
- `docs/MIGRATION.md` explains how the owner runs it on a secure machine or the server. Also write a **fake legacy fixture** for tests, so the importer is tested without real data.

### B9. Definition of Done (check all before opening the PR)

- [ ] `docker compose up` and a fresh Codespace both start the app, migrate and seed it without manual steps.
- [ ] All roles can log in with seeded demo accounts. Staff are forced to set up 2FA.
- [ ] A monitor can create a case, add parties and charges, and record a hearing on a phone-sized screen. An editor can approve it, and it then appears in dashboards and (anonymized) on the public portal.
- [ ] Every reference table has full CRUD, merge-duplicates and CSV import.
- [ ] Filters, saved filters, global search and exports work, and exports are logged.
- [ ] The dashboard shows all the widgets in step 6 using seeded data.
- [ ] The public portal works in Albanian and English, with no personal names (covered by an automated test).
- [ ] The researcher API works with a token, and its OpenAPI docs are reachable.
- [ ] The legacy importer passes its tests against the fake fixture.
- [ ] CI is green: Pint, Larastan and Pest on PostgreSQL. The tests cover policies, validation rules, workflow, anonymization and the importer.
- [ ] The README, DEPLOY, MIGRATION, DECISIONS, CHANGELOG docs and both user guides are written.
- [ ] The PR description lists what was built, how to try it, demo logins, and any known gaps.

### B10. Git rules for Claude Code

- Work on `build/v1`, branched from `main`. Later tasks get their own branches (`feature/*`, `fix/*`).
- Use Conventional Commits (`feat(hearings): …`, `fix(cases): …`, `security(auth): …`). Commit small and often, and keep each commit buildable.
- Never force-push, never push directly to `main`, and never rewrite history.
- Never commit `.env` files, dumps, exports or real personal data.

---

## Part C: Open questions for the organization (the build doesn't wait for these; defaults apply)

| Question | Default used in the build |
|---|---|
| May the names of public officials accused of corruption ever be public? | No names shown publicly |
| Serbian translation at launch? | Structure ready, not translated |
| Is review mandatory for every hearing? | Yes |
| Hosting provider and domain? | Documented for any VPS; domain configurable |
| Do funders require specific indicator or report formats? | Generic dashboards; add templates later |
| Will monitors attach court documents? | Supported, private by default |
