# Legacy migration

How to move the data of the old court-monitoring system (a Laravel 5-era admin panel on
MySQL) into CourtWatch with `php artisan legacy:import`.

> [!CAUTION]
> The old database contains real names. **Never** copy the dump, an export or a report into
> this repository, a ticket, a chat or a shared drive. Run the import **only on the
> production server or on a secure computer**, and delete the dump and the old database
> when you are done. `.gitignore` blocks `*.sql`, `*.dump`, `/migration/data/` and the
> `storage/` folder, but do not rely on it.

## What the importer does

The old system stores one flat row per hearing and defendant in seven modules:

| Module (key) | Legacy records (approx.) | Case type in CourtWatch |
| --- | --- | --- |
| Criminal – Corruption (`criminal_corruption`) | 28,700 | Criminal – corruption |
| Criminal – General Department (`criminal_general`) | 15,400 | Criminal – general |
| Criminal – Serious Crimes (`criminal_serious`) | 13,400 | Criminal – serious crimes |
| Criminal – Special Department (`criminal_special`) | 7,100 | Criminal – special department |
| Civil (`civil`) | 5,600 | Civil |
| Administrative Department (`administrative`) | 630 | Administrative |
| Economic Department (`economic`) | 50 | Economic |

For every module the importer:

1. **Reads the legacy table read-only.** The database session is switched to read-only mode
   (MySQL `SET SESSION TRANSACTION READ ONLY`), so the import cannot change the old data.
2. **Groups the flat rows into cases.** Rows of the same module and court that share a case
   number (court number or prosecution number, see `group_by`) are one case, even if early
   rows only had the prosecution number. Rows without any number become a case of their own.
3. **Removes duplicates from reference data.** Courts, branches, departments and prosecution
   offices are matched to the official structure (names in Albanian or English, codes, city
   names with Albanian endings — "Prishtinë", "Prishtinës", "Prishtina" — and aliases).
   Judges and prosecutors are matched by name regardless of case, spacing, titles or word
   order. Monitors are matched to existing monitor accounts by e-mail, name or code.
4. **Splits each case** into the case, its parties (accused, legal entities, injured parties,
   plaintiffs, respondents), charges, stages, measures, sentences, hearings, and the civil or
   administrative details.
5. **Maps free text to codes** where possible: "Po"/"jo" → yes/no, "Shqyrtimi gjyqësor" → main
   trial, "Mungesa e prokurorit; mungesa e të akuzuarit" → [prosecutor, accused], "1.500,00 €"
   → 1500, "15.03.2021" → 2021-03-15, broken encoding ("PrishtinÃ«") → "Prishtinë".
6. **Sends anything it cannot map to `legacy_review_items` instead of dropping it**: unknown
   courts or monitors, values that match no code, invalid dates and numbers, rows that disagree
   about the same case, inconsistent answers, stage dates out of order, legacy columns the
   mapping does not read, empty rows, and whole cases that failed to import. Raw values are
   stored **encrypted**; names never appear in clear text in the review table.
7. **Writes a reconciliation report**: for every module, how many legacy rows were imported,
   already imported, empty, failed or not read, and how many cases, parties, hearings and
   stages the new tables hold. "Check: OK" means every legacy row is accounted for.

The import is **idempotent**: every legacy row is recorded in `legacy_id_map`. Running it
again skips what was imported, adds new legacy rows (also to existing cases), and retries
cases that failed. It **never overwrites** records already imported, so corrections made by
staff after the first run are safe.

Imported cases and hearings get the status set by `LEGACY_IMPORT_STATUS` (`approved` by
default, so historical data counts in dashboards, the portal and research; use `submitted`
to have editors re-review everything). Individual records are not written to the audit log
(that would add hundreds of thousands of entries); each run writes one audit entry
("Legacy import") with the reconciliation counts.

## Before you start

- CourtWatch is installed and migrated, with the reference data seeded
  (`php artisan app:seed-if-empty`, with `SEED_DEMO_DATA=false` in production).
- **Create the monitor accounts first** (Administration → Users, role Monitor). Legacy rows
  name their monitor; the importer links hearings to an existing monitor by e-mail, name or
  monitor code. Names that match no account go to review (the hearing is still imported).
- A MySQL or MariaDB server that can hold a copy of the old database, on the server or the
  secure computer. PHP needs the `pdo_mysql` extension (it is in the CourtWatch images).
- A fresh backup of the CourtWatch database (`php artisan backup:run`).

## Step by step

### 1. Restore the old database (on the secure machine only)

```bash
mysql -u root -p -e "CREATE DATABASE legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

```bash
mysql -u root -p legacy < /secure/location/legacy-dump.sql
```

Create a user that can only read it:

```bash
mysql -u root -p -e "CREATE USER 'legacy_readonly'@'localhost' IDENTIFIED BY 'a-long-random-password'; GRANT SELECT ON legacy.* TO 'legacy_readonly'@'localhost';"
```

### 2. Point CourtWatch at it

In `.env` (never commit it):

```dotenv
LEGACY_DB_DRIVER=mysql
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=legacy
LEGACY_DB_USERNAME=legacy_readonly
LEGACY_DB_PASSWORD=a-long-random-password
LEGACY_DB_CHARSET=utf8mb4
LEGACY_IMPORT_STATUS=approved
```

If the old application stored UTF-8 text through a Latin-1 connection, keep `utf8mb4`: the
importer repairs the resulting "Ã«"-style text automatically.

### 3. Compare the mapping with the real schema

```bash
php artisan legacy:inspect --connection=legacy
```

This lists every legacy table, its row count and its columns next to the mapping in
`migration/config/legacy.php` — **names and counts only, never values**. Edit the mapping
until the command reports "The mapping matches the legacy database":

- `modules.*.table`: the legacy table of each module.
- `modules.*.columns`: field in CourtWatch → legacy column (`null` if the old system has none).
  The field list is below.
- `modules.*.stages`: stage → legacy columns for its `date`, `outcome` and `appellant`.
- `modules.*.defaults.department`: department code used when a row has no department.
- `group_by`: which numbers identify a case.
- `aliases`: extra spellings of courts, departments, criminal codes and monitors.
- `values`: extra spellings for codes. Every code already matches its own value and its
  Albanian and English labels, so only list real variants found in the old data.
- `ignore_columns`: bookkeeping columns that should not be reported as unmapped.

Columns that exist in a legacy table but are not mapped are not lost: their values go to
review (reason "Columns not in the mapping"). Map every column that holds data you need.

### 4. Trial runs

```bash
php artisan legacy:import --connection=legacy --dry-run --limit=200
```

A dry run does the whole import inside a transaction and rolls it back: only the report
remains (`storage/app/private/legacy/reconciliation-*-dry-run.md` and `.json`). Look at
"Review items added in this run": many items of the same kind usually mean a mapping or a
spelling list to extend. Adjust the mapping and repeat, then do a full dry run without
`--limit`. `--module=civil` limits a run to one module (repeatable).

### 5. The real import

```bash
php artisan legacy:import --connection=legacy --force
```

`--force` skips the confirmation that Laravel asks for in production. Run it inside `tmux`
or `screen`: about 70,000 rows take a while. Each case is saved in its own transaction; if a
case fails, it is recorded (reason "Import failed", with the error and the raw rows,
encrypted) and the import continues.

### 6. Check the result

- The report must say **"every legacy row is accounted for"** and show **OK** for every
  module. Compare "Legacy rows" with the counts of the old system.
- In the staff panel, **Administration → Legacy review** lists the open items with filters by
  module, reason and field. Each item shows the legacy row, the raw value (decrypted, staff
  only) and a link to the imported case. Correct the case if needed, then mark the item as
  resolved (with a note). Editors and Super Admins can do this.
- New reference values created by the importer (judges, prosecutors, articles, nationalities…)
  are **inactive**. Check them in **Reference data**, merge duplicates with "Merge duplicates",
  and activate the judges and prosecutors who are still serving.

### 7. Re-running

Run the same command again whenever the old system received new data, or after fixing the
mapping for rows that failed. Nothing is duplicated and nothing already imported is changed.

### 8. Clean up

When the migration is complete:

```bash
mysql -u root -p -e "DROP DATABASE legacy; DROP USER 'legacy_readonly'@'localhost';"
```

Delete the dump file from the machine, remove the `LEGACY_DB_*` values from `.env`, and keep
the reconciliation reports only as long as you need them (they hold counts, not names).

## Field reference

| Area | Fields |
| --- | --- |
| Case | `court`, `court_branch`, `department`, `division`, `judge`, `prosecutor`, `prosecution_office`, `court_case_number`, `indictment_number`, `police_case_number`, `summary`, `monitor` |
| Criminal party (the row's defendant) | `party_name`, `party_gender`, `party_nationality`, `party_position`, `party_economic_status`, `party_counsel`, `party_counsel_at_police`, `injured_party_name` |
| Charge | `criminal_code`, `article`, `offence` (title, used when there is no article number) |
| Sentence (first instance) | `sentence_prison_years`, `sentence_prison_months`, `sentence_suspended_months`, `sentence_fine`, `requested_prison_months` |
| Measures | `seizure_requested`, `seizure_granted`, `confiscation_requested`, `confiscation_granted`, `security_measure`, `security_measure_date` |
| Hearing | `hearing_date`, `hearing_type`, `venue`, `is_public`, `started_late`, `delay_minutes`, `delay_cause`, `was_postponed`, `postponement_reasons`, `recording`, `interpretation_needed`, `interpretation_provided`, `phone_used`, `phone_users`, `security_adequate`, `security_comment`, `participants`, `notes`, `monitor` |
| Civil / economic | `plaintiff_name`, `respondent_name`, `claim_type`, `claim_value`, `filed_date`, `plaintiff_type`, `respondent_type`, `representation`, `legal_aid`, `mediation_attempted`, `civil_outcome`, `decision_date` |
| Administrative | `plaintiff_name`, `plaintiff_type`, `respondent_body`, `first_instance_decision`, `first_instance_decision_date`, `decision_reasoned`, `decision_received_date`, `legal_remedy_type`, `legal_remedy_date`, `second_instance_outcome`, `second_instance_decision_date`, `conflict_filed`, `conflict_filed_date`, `representation`, `first_review_date`, `last_review_date` |
| Stages (`stages`) | any stage code (`criminal_report_filed`, `indictment_filed`, `indictment_decision`, `plea_agreement`, `judgment_announced`, `appeal_filed`, `appeal_decision`, …) with `date`, `outcome` and `appellant` columns |

Rules applied to the values:

- **Case-level values** (court, judge, numbers, stage dates, details) come from the latest row
  that has them (by hearing date). When rows disagree, the latest value is used and the
  disagreement goes to review.
- **Multi-value fields** (`postponement_reasons`, `participants`, `phone_users`) are split on
  `, ; / |` and each part is mapped separately.
- **Dates** are accepted as `2021-03-15`, `15.03.2021`, `15/03/2021`, `15-03-2021`, `15.03.21`
  and with a time. Impossible dates (30.02), dates before 1990 or in the future go to review;
  a hearing with an invalid date is not created, and all its answers are kept in the review
  item for manual entry.
- **Criminal code**: from the `criminal_code` column (aliases such as "KPRK", "KPK", "KPPK"),
  otherwise by the date of the case (`criminal_codes_by_date`). Unknown article numbers create
  an inactive offence and a review item.
- **Legal entities**: an accused whose gender is "person juridik" or whose name contains
  "SH.P.K.", "L.L.C.", "SH.A." and similar is imported as a legal entity.

## Trying it with fake data

The toolkit includes a fake legacy database with the same kinds of problems as the real one
(spelling variants, broken encoding, unknown courts, invalid dates, empty rows, unmapped
columns). Use it to see the importer at work without real data:

```bash
php artisan legacy:fixture
```

```bash
LEGACY_DB_DRIVER=sqlite LEGACY_DB_DATABASE=$PWD/storage/app/private/legacy/fixture.sqlite php artisan legacy:import --connection=legacy --dry-run
```

The automated tests use the same fixture (in memory):

```bash
php artisan test tests/Feature/Migration tests/Unit/LegacyMappingTest.php
```

## Troubleshooting

| Problem | What to do |
| --- | --- |
| "Database connection [legacy] is not configured" / "Cannot connect" | Check the `LEGACY_DB_*` values and that MySQL accepts the read-only user from this host. |
| "mapped column(s) missing" | The mapping names a column the legacy table does not have: run `legacy:inspect` and fix `migration/config/legacy.php`. |
| Many "Value could not be mapped" items for one field | Add the spellings to `values` (or `aliases` for courts, departments, codes, monitors) and do another dry run. Settle this **before** the real import: a real run never changes rows it already imported, so values fixed in the mapping afterwards must be corrected in the panel. |
| Many "Unknown reference value" items for monitors | Create the monitor accounts, or map legacy names to e-mails in `aliases.monitor`. |
| Text such as "PrishtinÃ«" in the panel | Set `LEGACY_DB_CHARSET` to the charset the old tables really use and re-import. |
| "Import failed" items | Open the item: the encrypted details contain the error and the raw rows. Fix the cause and run the import again; failed cases are retried. |
| The report shows MISMATCH | A legacy row was neither imported nor reported. Keep the report and the log (`storage/logs`) and do not delete the old database. |
