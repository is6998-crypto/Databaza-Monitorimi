# Decisions log

Choices made where `PROJECT_PROPOSAL.md` was silent or ambiguous. Newest entries are
added at the bottom of each section. Format: **decision** — reason.

## Repository and tooling

- **Bootstrapped `main` with only `PROJECT_PROPOSAL.md`.** — The GitHub repository was empty, so the commit the owner was meant to make in step A1 was made by the build to give `build/v1` (and the PR) a base. Nothing else was pushed to `main`.
- **Laravel 13, Filament 5.11, Livewire 4, Tailwind 4, Pest 4, Larastan 3, PHP 8.3.** — Latest stable versions at build time that resolve together.
- **Composer dependencies are locked (`composer.lock`) and npm dependencies via `package-lock.json`.** — Reproducible CI, Codespaces and Docker builds.
- **No web fonts from third-party CDNs; the system font stack is used.** — Avoids sending visitor IPs to font providers (data-protection) and keeps the PWA offline-capable.
- **The dev stack uses `php artisan serve` (4 workers) inside a PHP 8.3 CLI image; production uses FrankenPHP behind Caddy.** — Simplest dev loop; one-process production server with auto-TLS in front.
- **`app:seed-if-empty` seeds only an empty database.** — Makes `docker compose up` and Codespaces re-runnable without wiping data.
