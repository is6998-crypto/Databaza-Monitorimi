# CourtWatch — staff user guide

This guide is for the organization's staff: **monitors**, **editors** (program managers),
**analysts** and **administrators**. The Albanian version is
[user-guide-sq.md](user-guide-sq.md).

## 1. Signing in

1. Open the staff panel at `/admin` (for example `https://courtwatch.example.org/admin`).
2. Enter your e-mail and password.
3. **The first time**, you must set up two-factor authentication (2FA):
   - install an authenticator app on your phone (Aegis, Google Authenticator, Microsoft
     Authenticator, 1Password…);
   - press **Set up**, confirm your password, scan the QR code (or type the code shown under it);
   - type the 6-digit code from the app and press **Enable authenticator app**;
   - **save the recovery codes** somewhere safe (a password manager or on paper in a locked
     drawer): each one replaces the app once if you lose your phone.
4. From then on, every sign-in asks for the 6-digit code from the app.

Good to know:

- The language (Shqip / English) is in the menu under your initials, top right.
- After **30 minutes without activity** you are signed out. Two minutes before, a window asks
  whether you want to stay signed in; activity in any tab keeps all tabs alive. Forms are
  saved as drafts before the sign-out, so you do not lose your work.
- After several wrong passwords or codes, sign-in is blocked for a few minutes. If you lost your
  phone and your recovery codes, ask an administrator to reset your 2FA.

## 2. Who can do what

| Role | Can do |
| --- | --- |
| Monitor | Create cases and hearings in their assigned courts; write the report of every hearing; edit their own drafts and records that are being reviewed; cannot delete |
| Editor | Review, approve or return submissions; edit any case; set the priority of cases; read, edit and comment on hearing reports; manage reference data; publish reports |
| Analyst | Read everything, use the dashboard and export (read-only) |
| Super Admin | Everything, including users, roles, researcher approvals and deletions |

The panel shows only what your role allows, and the server checks every action again.

## 3. The menu

| Group | Contains |
| --- | --- |
| **Dashboard** (top) | Indicators and charts, or your own drafts and returned hearings |
| **Monitoring** | Review queue, Cases, Hearings, Hearing reports, Legal time limits |
| **Analytics** | Export log |
| **Public portal** | Reports & publications |
| **Reference data** | Courts, branches, departments, judges, prosecutors, criminal offences and other lists |
| **Administration** | Users, roles, Researchers, Audit log, Legacy review |

Press **Ctrl+K** (or **Cmd+K**) anywhere to search: case, indictment or police numbers (small
typos are tolerated), judge names, offences, and party names (whole names or surnames only).

## 4. Cases

A case is entered **once**; every hearing is attached to it, so case data is never typed again.

### Creating a case

1. **Monitoring → Cases → New case**.
2. Choose the case type (criminal — corruption, serious crimes, general, special department;
   administrative; civil; economic), the court, branch or chamber and department, the judge and
   prosecutor, and the case numbers. Only the departments of the chosen court are offered: the
   Special Department in Prishtina only, and branches have the General Department only.
3. On a criminal case, answer the four questions under **Procedural time limits**: is the
   accused in detention, who tries the case (a single judge or a panel), was the investigation
   extended, and is it a complex case. They decide which legal time limit applies (see *Legal
   time limits*).
4. If a case with the same number already exists in that court, a warning offers to open it
   instead: avoid duplicates.
5. Administrative, civil and economic cases have extra fields (decision, legal remedy, claim,
   representation…), shown only for those types.
6. Press **Create**. The case starts as a **Draft**.

The most recently used values (courts, judges…) appear first in the lists.

### The case workspace

Opening a case shows tabs:

| Tab | What you do there |
| --- | --- |
| **Overview** | The case data and its workflow status. On a criminal case it opens with the **Path of the case**: the eight steps from the criminal report to the execution, the step the case stands on, and the exceeded time limit if there is one |
| **Parties & charges** | Accused persons and legal entities, injured parties, plaintiffs and respondents, with gender, nationality, position, economic status, counsel; and the charges (criminal code article) at each stage |
| **Stages timeline** | Criminal report → investigation → indictment → initial hearing → main trial → judgment → appeal → Supreme Court, with dates and outcomes. Dates out of order are refused with an explanation. On a criminal case the **Legal time limits** sub-tab shows the limit of each phase |
| **Measures & sentences** | Security measures, seizure and confiscation (requested / granted), requested and imposed sentences |
| **Hearings** | All monitored hearings of the case; add a new one here |
| **Documents** | Attachments (PDF, JPG, PNG up to 20 MB). Files are private: downloads use links that expire after a few minutes |
| **History** | Every status change, with the comments of the reviewers |

> [!IMPORTANT]
> Party names are personal data. They are stored encrypted, never appear on the public portal,
> in research data or exports, and must not be copied into free-text fields such as notes.

### Case priority

Editors can mark a case as **high**, normal or **low** priority and write the reason (e.g. "accused in
detention"). The priority is set in the case form, with **Set priority** on the case page (also when the
case is published), or for many cases at once: select the cases in the list and open the bulk actions.

High-priority cases come first in the case list, the review queue and the monitor app, where they carry a
yellow label. The list also has a priority filter. Monitors see the priority but cannot change it.
Changing the priority does not reopen the review, and it never appears on the public portal, to
researchers or in the API.

### Legal time limits

The Criminal Procedure Code sets time limits for the phases of the procedure. The platform works
them out from the dates you enter under **Stages** and from the answers under **Procedural time
limits**:

| Phase | Limit | Article |
| --- | --- | --- |
| Investigation (from its start to its end) | 2 years; 3 years when extended | 157 |
| Filing of the indictment (after the investigation ends) | 3 months | 234 |
| Initial hearing (after the indictment is filed) | 30 days; 15 days in detention | 236 |
| Scheduling of the main trial (after the indictment is confirmed) | 30 days | 249 |
| Main trial (from its start to its end) | 90 days before a single judge; 120 days before a panel | 310 |
| Announcement of the judgment (after the main trial ends) | 3 days | 365 |
| Written judgment (after the announcement) | 15 days in detention; 30 days otherwise; 60 days in a complex case | 368 |
| Appeal (from the day the Court of Appeals receives the file), detention only | 3 months | 405 |

The Code sets no limit for objections to evidence and requests to dismiss the indictment.

- **Monitoring → Legal time limits** lists them all: *Exceeded*, *Ending soon* (within 7 days),
  *Within the limit* and *Finished late*. The badge in the menu counts the limits exceeded right
  now; the dashboard shows the same.
- A phase closes when you enter the date of the stage that ends it (for example *Judgment
  drafted*). Enter dates as soon as you learn them.
- **Notifications.** Every morning, when a limit has been exceeded, the monitor of the case, the
  editors and the administrators are notified (bell and email): once per limit, and once more if
  the longest limit the law allows passes too (60 days for the judgment, 3 years for the
  investigation).
- **Warning.** The same people are warned once when 80% of a limit is used (e.g. day 48 of 60),
  but never more than a month before it ends, so that there is still time to follow the case.
  Limits shorter than a week get no warning.
- When an answer is missing the longer limit applies (no detention, a panel), so that nobody is
  warned too early. The list shows on what basis a limit was counted.

## 5. Recording a hearing

1. In the case, open **Hearings → New hearing** (or **Monitoring → Hearings → New hearing** and
   choose the case). Court, judge and prosecutor are filled in from the case.
2. Answer the questions. Follow-up questions appear only when needed — for example the delay
   minutes and cause only if the hearing started late, the reasons only if it was postponed,
   who used a phone only if a phone was used.
3. The hearing date cannot be in the future.
4. The form **saves a draft automatically** every 20 seconds and when you are signed out; when
   you come back, the draft is restored.
5. If the court set the next session, note it under **Next hearing** (date and time). It is added
   to the hearing calendar.
6. Save the hearing, then press **Submit for review** when it is complete.

### The hearing calendar

**Monitoring → Hearing calendar** shows the month with the hearings coming up: time, case and who
attends. On a phone the same month is a list of days. Filter by court or show only the hearings you
attend; **List** gives the same with tabs (*Coming up*, *Not monitored*, *Monitored*, *Cancelled*).

- Hearings are added from the **Next hearing** noted at each hearing (also in the monitor app), or
  by hand: **Create** in the calendar, **+** on a day, or **Add to calendar** on the case page
  (from the court's published schedule, say).
- **Who attends.** The monitor of the case is set automatically. Editors can choose someone else;
  a monitor can take a hearing nobody attends with **I will attend**.
- **Reminder.** The day before (at 14:00) the monitor who attends gets a reminder with the time,
  the case and the courtroom.
- **High priority, no monitor.** Hearings of high-priority cases in the next 7 days that nobody
  attends are shown in yellow, and editors and administrators are notified once.
- When the hearing of that day is recorded for that case, the planned hearing is marked
  **Monitored**. A day that passed without a recorded hearing shows as **Not monitored**; from there
  **Record the hearing** opens the form at once.
- If the court cancels or postpones the hearing, use **Cancel** and add the new date as a new
  hearing.

The calendar is for the team only: it is not shown on the public portal, to researchers or in the
API.

### The hearing report

For every hearing the monitor writes a report: what happened, in their own words.

1. On the hearing press **Write the report** (or **Monitoring → Hearing reports → Write report**
   and choose the hearing). A hearing has one report.
2. Write the title and the text: who was present, what was said and decided, when the next
   hearing is. Save it; it stays a **Draft** until you send it.
3. Press **Send to the editor**. The editors are notified and the report appears in the **Review
   queue**.
4. The **editor** reads it, may edit the text (**Edit**), leaves comments under the report, and
   then presses **Approve** or **Send back with a comment**.
5. The monitor is notified of every comment and can answer in the same place. A report that was
   sent back is completed and sent again. After approval the monitor can no longer change it.

A monitor sees only their own reports; editors see all of them. The text and the comments are
stored encrypted and stay inside the panel: they never appear on the public portal, in the
researcher area or in email notifications.

**Download** on the report page gives the report as **Word** (to edit, e.g. for a publication) or
**PDF** (to print), with or without the comments. The file is marked "Internal — contains personal
data"; every download is logged in the **Export log**.

### The monitor app (phone, works offline)

The menu under your initials has **Monitor app (mobile)** (`/m`). On a phone, add it to the home
screen ("Install" / "Add to Home Screen").

- It lists the cases of your courts; choose one and fill in the short hearing form.
- **Without internet**, the hearing is kept on the phone ("Offline"); it is sent automatically
  when the connection returns, and never sent twice.
- **Save draft** keeps it on the phone; **Send for review** submits it.
- Do not sign out or clear the browser data while hearings are waiting to be sent.
- Once a hearing has been sent, **Write the report** opens the report page in the panel (needs a
  connection).

## 6. Review workflow

Every case and hearing goes through these statuses:

**Draft → Submitted → In review → Approved → Published**, or **Returned** (with a comment) for
corrections.

- **Monitors** press **Submit for review**. A returned record shows the reviewer's comment; correct
  it and submit it again.
- **Editors** open **Monitoring → Review queue** (the badge shows how many wait), press **Start
  review**, look at **View changes** (what changed since the last reviewed version), then:
  - **Approve**: the record now counts in the dashboard, the public portal (anonymized) and
    research data;
  - **Return with comment**: a comment is required, and the monitor is notified;
  - **Publish** freezes an approved record (only a Super Admin can still edit it); **Unpublish**
    reopens it.
- Notifications appear under the bell, top right, and by e-mail.

Only **approved** and **published** records are used for statistics, the portal and research.

## 7. Lists, filters and exports

- Every list has a search box and filters (court, type, status, dates, judge…). The filters stay
  in the address bar, so you can share the link with a colleague.
- **Save filters** stores the current filters under a name, for yourself or shared with the team.
- Select rows to use the bulk actions: assign a monitor, change the status (through the review
  rules), export the selected rows, delete (Super Admin only).
- **Export** writes the filtered list to CSV or Excel. Every export is recorded in
  **Analytics → Export log**, and exports never contain party names.

## 8. Dashboard

**Dashboard** shows, for the period, court and case type you choose: hearings over time,
postponements (rate and reasons, by court, judge and case type), punctuality and delay causes,
public and closed hearings, recording and interpretation, median days between stages, indictment
outcomes, convictions and acquittals, sentences, plea agreements, appeal outcomes, and seizure
and confiscation requested and granted. Monitors see their drafts and returned hearings instead.

### Period reports (editors and analysts)

**Analytics → Period reports** writes a monitoring report for a quarter, half year, year or other
dates, for funders and partners, as **Word** or **PDF**. Choose the period (and a court or case type
if you like); the card beside it shows how many cases and hearings it covers.

The report has: the summary (cases, hearings, courts, postponements, delays, openness, judgments),
cases by court, type and offence category, postponement reasons, punctuality and delay causes,
openness, recording and interpretation, court decisions, the legal time limits that ended and how
many were exceeded, and the method. Only approved data is counted. **Hide numbers under 5** is on:
keep it on for every report that leaves the team. Every download is logged.

### Monitoring coverage (editors and analysts)

**Monitoring → Coverage** shows where the monitoring has gaps, with a tile for each (yellow while
something needs doing) and a list under it:

- **Active cases not followed**: cases with a legal time limit still running or a high priority,
  with no monitored hearing in the last 60 days;
- **High priority, no monitor**;
- **Planned hearings not monitored** in the last 30 days (from the calendar);
- **Hearings without a report**: hearings of the last 90 days, older than 3 days, with no written
  report;
- **Missing answers for the time limits**: criminal cases where detention or who tries the case
  is unknown;
- **Forgotten drafts**: cases and hearings in draft or returned, untouched for 14 days.

Each row opens the case or hearing. The numbers of days are in `config/courtwatch.php`
(`coverage`).

## 9. Reference data (editors and administrators)

**Reference data** holds the lists used in the forms (courts, branches, departments, divisions,
prosecution offices, judges, prosecutors, monitors, applicants, criminal codes and offences,
categories, nationalities, positions, economic statuses, security measure types).

- Each entry has an Albanian and an English name and can be made inactive instead of deleted.
- **Merge duplicates**: select the duplicates, choose the one to keep; every case and hearing is
  moved to it and the others are archived.
- **Import CSV**: download the template, fill it in, import it; a summary shows what was
  created, updated or skipped.

## 10. Public portal content (editors)

**Public portal → Reports & publications**: upload the organization's reports (PDF) with a
title and summary in both languages, and tick **Published** to show them on the portal. The
portal's overview of monitored cases (how many cases and hearings, by year, court, type and
offence category) and its anonymized cases update by themselves from approved data; numbers
below 5 are shown as "<5" to protect privacy. The findings of the monitoring (postponements,
delays, judgments, durations) are not published on the portal: they stay in the panel and the
researcher area.

## 11. Administration (Super Admin)

- **Users**: create accounts, choose the role, assign **courts to monitors**, deactivate people who
  leave (do not delete them), and **reset 2FA** for someone who lost their phone.
- **Roles**: the permissions of each role (change with care).
- **Researchers**: approve, reject (with a reason) or suspend researcher accounts. Suspending
  revokes their API tokens at once.
- **Audit log**: who signed in (and failed), what was created, changed or deleted, exports and
  permission changes.
- **Legacy review**: values from the old database that the import could not map. Correct the
  imported case if needed, then mark the item as resolved.
- The queue dashboard (`/horizon`) is for Super Admins with 2FA.

## 12. Rules for everyone

- Never put real personal data in demo or test systems, and never send exports or screenshots with
  names outside the organization.
- Do not share your account or your 2FA codes; sign out on shared computers.
- Report a lost phone or a suspected account problem to an administrator immediately.

## 13. Help

| Problem | What to do |
| --- | --- |
| "Too many attempts" at sign-in | Wait the minutes shown, then try again carefully. |
| Lost phone / no codes | An administrator resets your 2FA; you set it up again at the next sign-in. |
| I do not see a case | Monitors only see cases in their assigned courts; ask an administrator. |
| A date is refused | The stage dates must follow the order of the proceedings; check the message. |
| Hearing stuck on the phone | Open `/m` with internet; it is sent automatically. Do not clear the browser data. |
