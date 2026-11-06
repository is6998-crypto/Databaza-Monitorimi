# Deployment

CourtWatch runs on a single virtual private server (VPS) with Docker Compose:

| Container | Role |
| --- | --- |
| `app` | FrankenPHP: Caddy web server with **automatic HTTPS** (Let's Encrypt) + PHP. Runs the migrations on start. Ports 80 and 443. |
| `horizon` | Queue worker (e-mails, notifications). |
| `scheduler` | Scheduled tasks: daily encrypted backups, backup monitoring, housekeeping. |
| `postgres` | PostgreSQL 16 (not reachable from outside). |
| `redis` | Redis 7 for sessions, cache and queues (password-protected, not reachable from outside). |

Uploaded files live on S3-compatible object storage (or on the server's disk, see
[Files](#files)). Backups go to a second, **off-site** S3-compatible bucket.

Files: `Dockerfile`, `docker-compose.prod.yml`, `docker/prod/` (Caddyfile, PHP settings,
entrypoint) and `.env.production.example`.

## 1. What you need

- A VPS with Ubuntu 24.04 LTS, 2 vCPU, 4 GB RAM and 40 GB disk (more disk if files are stored
  locally), with a public IPv4 address.
- A domain name, e.g. `courtwatch.example.org`, with an `A` record (and `AAAA` for IPv6) pointing
  to the VPS. Ports 80 and 443 must be reachable for the certificate.
- An S3-compatible bucket for uploaded files (private), and **a second bucket at another provider
  or region for backups**.
- An SMTP account for e-mail (password resets, notifications, backup reports).

## 2. Prepare the server

Log in as root once, create a user with sudo rights and SSH-key login, then as that user:

```bash
sudo apt update && sudo apt -y upgrade && sudo apt -y install unattended-upgrades ufw git
```

```bash
sudo ufw allow OpenSSH && sudo ufw allow 80/tcp && sudo ufw allow 443/tcp && sudo ufw allow 443/udp && sudo ufw enable
```

Install Docker Engine and the Compose plugin following the official instructions
(<https://docs.docker.com/engine/install/ubuntu/>), then let your user run Docker:

```bash
sudo usermod -aG docker "$USER"
```

Log out and in again. Disable SSH password login (`PasswordAuthentication no` in
`/etc/ssh/sshd_config`) once your key works.

## 3. Get the code and configure it

```bash
git clone https://github.com/is6998-crypto/Databaza-Monitorimi.git courtwatch && cd courtwatch
```

```bash
cp .env.production.example .env && chmod 600 .env
```

Generate the secrets (run each command and paste the output into `.env`):

```bash
echo "APP_KEY=base64:$(openssl rand -base64 32)"
```

```bash
echo "BLIND_INDEX_KEY=$(openssl rand -hex 32)"
```

```bash
echo "DB_PASSWORD=$(openssl rand -hex 24)"; echo "REDIS_PASSWORD=$(openssl rand -hex 24)"; echo "BACKUP_ARCHIVE_PASSWORD=$(openssl rand -base64 32)"
```

Then fill in the rest of `.env`:

- `APP_URL=https://courtwatch.example.org` and `APP_DOMAIN=courtwatch.example.org`
  (`CADDY_GLOBAL_OPTIONS="email you@example.org"` for certificate notices).
- `MAIL_*`: your SMTP account. `BACKUP_NOTIFICATION_EMAIL`: who receives backup reports.
- `AWS_*`: the bucket for uploaded files (`AWS_ENDPOINT` for providers other than Amazon).
- `BACKUP_S3_*`: the **off-site** backup bucket.
- Keep `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` and `SEED_DEMO_DATA=false`.

> [!IMPORTANT]
> Store `APP_KEY`, `BLIND_INDEX_KEY` and `BACKUP_ARCHIVE_PASSWORD` in a password manager
> outside the server. **Never change `APP_KEY` or `BLIND_INDEX_KEY` after go-live**: party
> names are encrypted with `APP_KEY` and found through `BLIND_INDEX_KEY`; a restored backup is
> unreadable without the same keys, and the archives cannot be opened without the backup password.

## 4. Start

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

The first start builds the image, waits for PostgreSQL, runs the migrations and seeds the
reference data (courts, departments, prosecution offices, criminal codes). Caddy obtains the
TLS certificate within a minute. Check:

```bash
docker compose -f docker-compose.prod.yml ps
```

```bash
curl -fsS https://courtwatch.example.org/up
```

`/up` answers 200 only when the application, the database and Redis all work.

## 5. Create the first administrator

```bash
docker compose -f docker-compose.prod.yml exec app php artisan app:create-admin admin@example.org --name="First Last"
```

Sign in at `https://courtwatch.example.org/admin`: two-factor authentication must be set up
at the first sign-in (an authenticator app such as Aegis, Google Authenticator or 1Password).
Then create the staff accounts in **Administration → Users** (monitors with their courts,
editors, analysts). Every staff member sets up 2FA at their first sign-in.

## Files

Attachments and publications are private and only served through signed, expiring links.

- **S3-compatible storage** (default, `FILESYSTEM_DISK=s3`, `ATTACHMENTS_DISK=s3`): create a
  private bucket (or run `docker compose -f docker-compose.prod.yml exec app php artisan app:ensure-bucket`),
  enable **versioning** on it and, if the provider offers it, replication to another region.
  The bucket is not part of the backup archive.
- **Local disk** (`FILESYSTEM_DISK=local`, `ATTACHMENTS_DISK=local`): files are stored in the
  `storage` volume and **are included** in the daily backup archive. Simplest option for a small
  installation.

## 6. Backups

The scheduler runs every night (times in UTC):

| Time | Task |
| --- | --- |
| 01:30 | `backup:clean`: keeps every backup for 7 days, then daily ones for 30 days, weekly for 8 weeks, monthly for 12 months, yearly for 2 years |
| 02:00 | `backup:run`: PostgreSQL dump (gzip) + private files, in a zip **encrypted with AES-256** (`BACKUP_ARCHIVE_PASSWORD`), uploaded to the off-site `backups` disk |
| 07:00 | `backup:monitor`: e-mails `BACKUP_NOTIFICATION_EMAIL` if the newest backup is older than a day or too large |

Run a backup by hand and list the backups:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan backup:run
```

```bash
docker compose -f docker-compose.prod.yml exec app php artisan backup:list
```

### Restore

Practise a restore at least every three months on a spare server.

1. Download the archive (e.g. `2026-11-05-02-00-01.zip`) from the backup bucket with the
   provider's console or any S3 client.
2. Unpack it with the backup password (AES-256 zip archives need 7-Zip):

   ```bash
   7z x 2026-11-05-02-00-01.zip -p"$BACKUP_ARCHIVE_PASSWORD" -orestore
   ```

   The archive holds `db-dumps/postgresql-courtwatch.sql.gz` and `private/` (local files).
3. On the server, with the **same `.env`** (same `APP_KEY` and `BLIND_INDEX_KEY`), stop the
   application containers and recreate the database:

   ```bash
   docker compose -f docker-compose.prod.yml stop app horizon scheduler
   ```

   ```bash
   docker compose -f docker-compose.prod.yml exec postgres sh -c 'dropdb -U "$POSTGRES_USER" --if-exists "$POSTGRES_DB" && createdb -U "$POSTGRES_USER" "$POSTGRES_DB"'
   ```

   ```bash
   gunzip -c restore/db-dumps/postgresql-courtwatch.sql.gz | docker compose -f docker-compose.prod.yml exec -T postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"'
   ```

4. Restore local files (only if files are stored locally):

   ```bash
   docker compose -f docker-compose.prod.yml cp restore/private/. app:/app/storage/app/private/
   ```

5. Start again and check `/up`, a few cases and an attachment:

   ```bash
   docker compose -f docker-compose.prod.yml up -d
   ```

## 7. Updates

```bash
git pull && docker compose -f docker-compose.prod.yml up -d --build && docker image prune -f
```

Migrations run automatically when the `app` container starts. To run them yourself after a
fresh backup, set `RUN_MIGRATIONS=false` and run:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

Rebuild at least monthly to get PHP, Caddy and Debian security fixes, and merge the Dependabot
pull requests after CI is green.

## 8. Monitoring and logs

- Uptime: monitor `https://courtwatch.example.org/up` (database and Redis included) with any
  uptime service.
- Logs: `docker compose -f docker-compose.prod.yml logs -f app` (web and application),
  `horizon` and `scheduler` likewise.
- Queues: `/horizon`, for Super Admins with 2FA only.
- Audit: **Administration → Audit log** (logins, failed logins, changes, exports, permission
  changes) and **Analytics → Export log**.

## 9. Security checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`; the error pages show no technical details.
- [ ] HTTPS works and HTTP redirects to HTTPS; HSTS and the security headers are sent
      (check with `curl -I https://courtwatch.example.org/sq`).
- [ ] Only ports 22, 80 and 443 are open (`sudo ufw status`); PostgreSQL and Redis are not published.
- [ ] `.env` is readable by its owner only and is not in any backup outside the server.
- [ ] The keys and the backup password are in a password manager.
- [ ] A backup arrived in the off-site bucket, and a restore was tested.
- [ ] Every staff account has 2FA (enforced at sign-in); former staff are deactivated, not deleted.
- [ ] The Super Admin role is limited to the people who need it.
- [ ] Researchers are approved one by one; their exports are visible in the export log.

Sign-in is limited to 5 attempts per minute per IP address, and an account is locked for 15
minutes after 10 failed attempts (`LOGIN_MAX_ATTEMPTS`, `LOGIN_LOCK_MINUTES`). Behind a load
balancer or CDN, set `TRUSTED_PROXIES` so the real client addresses are used.

## Legacy migration

The import of the old database runs on this server (or a secure computer), never in the
repository: see [MIGRATION.md](MIGRATION.md). Example inside the running stack, with the
`LEGACY_DB_*` values in `.env` pointing at a MySQL server the `app` container can reach:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan legacy:import --connection=legacy --dry-run
```

## Troubleshooting

| Problem | What to check |
| --- | --- |
| No certificate / browser warning | DNS points to the server, ports 80 and 443 are open, `APP_DOMAIN` is right; see `docker compose -f docker-compose.prod.yml logs app`. |
| `app` keeps restarting | `APP_KEY` or `BLIND_INDEX_KEY` missing (the log says which), or the database password does not match the existing volume. |
| `/up` returns 500 | Database or Redis down: `docker compose -f docker-compose.prod.yml ps` and the `postgres`/`redis` logs. |
| E-mails not sent | `MAIL_*` settings; `horizon` must be running (`docker compose -f docker-compose.prod.yml logs horizon`). |
| Backups missing | `docker compose -f docker-compose.prod.yml logs scheduler`, the `BACKUP_S3_*` settings, and run `backup:run` by hand to see the error. |
