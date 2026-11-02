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

## Authentication, roles and auditing

- **Filament's built-in app (TOTP) MFA, `isRequired: true` on the staff panel, with recovery codes.** — Meets "2FA mandatory for all staff roles" without an extra plugin. Researchers use a separate panel where 2FA is optional.
- **Permission names follow Filament Shield's `Affix:Subject` convention; the matrix lives in `App\Support\Permissions` and is seeded by `RolesAndPermissionsSeeder`.** — One source of truth, and Shield's role editor shows the same permissions. Policies are hand-written (Shield policy generation is off) because monitors need record-level rules.
- **Super Admin is granted everything through `Gate::before`.** — Matches "Everything" in B4; workflow state machine rules are still enforced by the workflow service.
- **Users are deactivated, never deleted from the panel.** — Keeps the audit trail and authorship intact.
- **Monitor scope = assigned courts ∪ cases they created ∪ cases assigned to them.** — B4 says monitors "see cases in their assigned courts"; they also need to see what they themselves created or were assigned.
- **Monitors may remove rows inside a case (a party, a charge) only while their case is a draft or returned; they can never delete cases or hearings.** — "No delete" is respected for records, while still allowing typo fixes during data entry.
- **Idle timeout is enforced server-side (`SESSION_LIFETIME=30`) and client-side (warning modal 2 minutes before, "Stay signed in" button, activity shared across tabs via `localStorage`).** — Never a hard lock; autosaving forms get a `courtwatch:before-idle-logout` event first.
- **Filament's default avatar provider (ui-avatars.com) is replaced by locally generated initials.** — The default would send staff names to a third party.
- **Audit log = spatie/laravel-activitylog with log names per area (`auth`, `permissions`, `cases`, `exports`…). Party names are never written to the audit log; only a `name_changed` flag.** — Otherwise the log would hold personal names in clear text, defeating encryption at rest.
- **Tests seed roles and the public reference structure once per run (`TestingSeeder`) and run each test in a transaction.** — Keeps the suite fast on PostgreSQL.

## Reference data

- **One generic Filament resource base (`ReferenceResource`) with a modal "manage" page per table.** — Sixteen tables share CRUD, bilingual names, search, soft delete/restore, merge and CSV import; each table only declares its extra fields.
- **`name_en` is nullable like `name_sr`; labels fall back to Albanian.** — Legacy and CSV data often lack English names; Albanian is the default language.
- **People tables (judges, prosecutors) have a single `name` column, not translated names; monitors take their name from the linked user.** — Personal names are not translated. These names are internal and never published.
- **Merge = pick the record to keep among the selected rows; every foreign key (listed in each model's `references()`) is re-pointed, rows that would break a unique constraint are dropped, duplicates are deactivated and soft-deleted, and the merge is audited.** — Matches "re-points all references" and keeps history recoverable.
- **Deleting reference rows is reserved to Super Admin; Editors deactivate or merge.** — Prevents accidental loss of data used by historical cases.
- **CSV import matches existing rows by Albanian name (or code / article number where they exist), updates them, creates the rest, and reports skipped rows with line numbers. Related tables are resolved by code or name. Comma or semicolon delimiters are detected.** — Excel in the region often saves CSV with semicolons.
- **The seeded court structure (7 basic courts with branches, Court of Appeals, Supreme Court), departments, prosecution offices, criminal codes and a *sample* of articles come from public sources and should be verified by the organization.** — The proposal asks for the real public structure; editors can correct it in the panel or by CSV.
- **No Commercial Court is seeded.** — B6.3 lists only basic courts, the Court of Appeals and the Supreme Court; it can be added in the panel.
