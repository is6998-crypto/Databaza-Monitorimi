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
