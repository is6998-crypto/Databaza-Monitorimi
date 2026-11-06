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
| Monitor | Create cases and hearings in their assigned courts; edit their own drafts and records that are being reviewed; cannot delete |
| Editor | Review, approve or return submissions; edit any case; manage reference data; publish reports |
| Analyst | Read everything, use the dashboard and export (read-only) |
| Super Admin | Everything, including users, roles, researcher approvals and deletions |

The panel shows only what your role allows, and the server checks every action again.

## 3. The menu

| Group | Contains |
| --- | --- |
| **Dashboard** (top) | Indicators and charts, or your own drafts and returned hearings |
| **Monitoring** | Cases, Hearings, Review queue |
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
   administrative; civil; economic), the court, branch and department, the judge and prosecutor,
   and the case numbers.
3. If a case with the same number already exists in that court, a warning offers to open it
   instead: avoid duplicates.
4. Administrative, civil and economic cases have extra fields (decision, legal remedy, claim,
   representation…), shown only for those types.
5. Press **Create**. The case starts as a **Draft**.

The most recently used values (courts, judges…) appear first in the lists.

### The case workspace

Opening a case shows tabs:

| Tab | What you do there |
| --- | --- |
| **Overview** | The case data and its workflow status |
| **Parties & charges** | Accused persons and legal entities, injured parties, plaintiffs and respondents, with gender, nationality, position, economic status, counsel; and the charges (criminal code article) at each stage |
| **Stages timeline** | Criminal report → investigation → indictment → initial hearing → main trial → judgment → appeal → Supreme Court, with dates and outcomes. Dates out of order are refused with an explanation |
| **Measures & sentences** | Security measures, seizure and confiscation (requested / granted), requested and imposed sentences |
| **Hearings** | All monitored hearings of the case; add a new one here |
| **Documents** | Attachments (PDF, JPG, PNG up to 20 MB). Files are private: downloads use links that expire after a few minutes |
| **History** | Every status change, with the comments of the reviewers |

> [!IMPORTANT]
> Party names are personal data. They are stored encrypted, never appear on the public portal,
> in research data or exports, and must not be copied into free-text fields such as notes.

## 5. Recording a hearing

1. In the case, open **Hearings → New hearing** (or **Monitoring → Hearings → New hearing** and
   choose the case). Court, judge and prosecutor are filled in from the case.
2. Answer the questions. Follow-up questions appear only when needed — for example the delay
   minutes and cause only if the hearing started late, the reasons only if it was postponed,
   who used a phone only if a phone was used.
3. The hearing date cannot be in the future.
4. The form **saves a draft automatically** every 20 seconds and when you are signed out; when
   you come back, the draft is restored.
5. Save the hearing, then press **Submit for review** when it is complete.

### The monitor app (phone, works offline)

The menu under your initials has **Monitor app (mobile)** (`/m`). On a phone, add it to the home
screen ("Install" / "Add to Home Screen").

- It lists the cases of your courts; choose one and fill in the short hearing form.
- **Without internet**, the hearing is kept on the phone ("Offline"); it is sent automatically
  when the connection returns, and never sent twice.
- **Save draft** keeps it on the phone; **Send for review** submits it.
- Do not sign out or clear the browser data while hearings are waiting to be sent.

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
portal's statistics and anonymized cases update by themselves from approved data; numbers below
5 are shown as "<5" to protect privacy.

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
