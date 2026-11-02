# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Conventional Commits](https://www.conventionalcommits.org/).

## [Unreleased] — v1 build

### Added
- Project scaffold: Laravel 13, Filament 5, Livewire 4, Tailwind 4, Pest, Larastan, Pint.
- Docker Compose dev stack (app, Horizon, scheduler, PostgreSQL 16, Redis, MinIO, Mailpit) and a Codespaces dev container that installs, migrates, seeds and serves automatically.
- GitHub Actions CI: Pint, Larastan and Pest on PostgreSQL 16; Dependabot for Composer, npm and Actions.
