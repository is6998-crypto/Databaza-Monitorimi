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
