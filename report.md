# Digital Health Passport: Workflow Audit

Read only audit of `C:\Users\Adaline\Desktop\dph-system`. Evidence is cited by file and line. Where something could not be confirmed in code it is stated as "not found in code". No secrets or `.env` values are reproduced.

The report has 8 sections:

1. Executive summary
2. Scope, roles, and what is really in the code
3. End to end workflow map
4. Passport capability fit and scope drift
5. Efficiency versus paper
6. Security, privacy, and error handling
7. Severity rated findings
8. Prioritised action plan and conclusion

---

## 1. Executive summary

The system as built is a genuine patient owned passport. A record belongs to the holder, it is carried on a printed card with a QR code, and a health worker can only read it after scanning that card or after proving identity with the passport number plus date of birth. This rule is enforced on the server, not in the menu: `tests/Feature/PassportScanFlowTest.php:39-42` proves a passport is forbidden before a scan, and `routes/web.php:98-104` shows every read and write path sits behind the `OpenPassports` permission and a policy.

The main risk is not the passport code. It is documentation drift and a small number of deployment and privacy settings. `docs/System Design & Requirements Document.md` describes a much larger hospital style system (appointments, laboratory, consents, enrollment approval, NIN hash, verifier accounts, super admin) that does not exist. `tests/Feature/PageAccessTest.php:63-68` explicitly asserts such routes must not exist, and `PageAccessTest.php:70-76` asserts there are only four roles. The reliable description of the built system is `docs/system-design.md`.

Three concrete issues stand out:

- The offline queue keeps raw QR tokens and typed notes in browser local storage and is not cleared on sign out (`resources/js/app.js:84-88` clears only the offline page cache, not the queue key defined at `resources/js/offlineQueue.js:6`). On a shared device this is the most serious privacy gap found. Rated High.
- Immunisation has no age based due or overdue logic. The vaccine catalogue carries a `recommended_age` column (`database/migrations/2026_01_01_000009_create_vaccination_and_reminder_tables.php:17`) but no code was found that compares it to the child's age. Rated Medium, because it is a clinical safety gap.
- Session hardening is left to the server. The documented defaults do not force secure cookies and do not encrypt the session (`config/session.php:50`, `config/session.php:172`, `.env.example:34`), and `APP_DEBUG=true` is the example default (`.env.example:4`). Rated Medium, and depends entirely on the live `.env`.

Overall the passport core (registration and card, scan first access, visit and vaccination record, portal, certificate verification, offline support) is coherent and test backed. The supporting admin modules (reports, backups, system health, reminders over SMS and email) are heavier than a pure passport needs and can be simplified or hidden per deployment.

---

## 2. Scope, roles, and what is really in the code

### 2.1 Roles

There are exactly four roles, confirmed by `tests/Feature/PageAccessTest.php:70-76`:

- `system_admin`, no facility, created by seeder (`app/Console/Commands/CreateAdminCommand.php`).
- `facility_admin`, belongs to a facility.
- `health_worker`, belongs to a facility.
- `patient`, the portal holder account, one per holder (`database/migrations/2026_01_01_000003_create_patients_tables.php:55` makes `users.patient_id` unique).

Permissions are grouped per route in `routes/web.php:54-139`. The verifier is not a role: certificate verification is a public signed route (`routes/web.php:28-29`).

### 2.2 Modules that exist

Grouped by the permission that guards them:

- Accounts and access: `routes/web.php:40-52`. Forced password change is wired through middleware (`bootstrap/app.php`, referenced by `routes/web.php:42-43`).
- Patient registration and card: `routes/web.php:105-116`, `app/Http/Controllers/Patients/PatientController.php`.
- Scan first access: `routes/web.php:98-104`, `app/Http/Controllers/Patients/AccessController.php`, `app/Services/PassportAccessService.php`.
- Visits and vaccinations: `routes/web.php:117-129`, `app/Http/Controllers/Clinical/*`.
- Reminders and notifications: `routes/web.php:49-52`, `routes/web.php:125-129`, `routes/console.php:10`.
- Patient portal: `routes/web.php:132-139`.
- Certificate verification: `routes/web.php:28-29`, `routes/web.php:111`.
- Admin: facilities, districts, facility administrators, settings, vaccines, backups, system health, audit log: `routes/web.php:55-81`.
- Facility admin: staff, profile, reports: `routes/web.php:84-95`.
- Offline: `routes/web.php:101-102`, service worker `public/sw.js`, queue `resources/js/offlineQueue.js`, scanner `resources/js/scanner.js`.

### 2.3 Modules that do not exist

Confirmed absent by test: `campaigns`, `appointments`, `admissions`, `pharmacy`, `facility.wards`, `visits.queue`, and `patients.index` (`tests/Feature/PageAccessTest.php:63-68`). There is no laboratory module, no triage or consultation split, no billing, and no patient list. A visit is one `Encounter` record (`database/migrations/2026_01_01_000005_create_encounters_and_access_tables.php:12-32`). Medicines are notes only, the system does not stock or dispense (`database/migrations/2026_01_01_000005_create_encounters_and_access_tables.php:34`).

---

## 3. End to end workflow map

### 3.1 Health worker, routine visit

1. Sign in, land on the health worker dashboard (`resources/views/dashboards/health-worker.blade.php`).
2. Open a passport: scan card or use passport number plus date of birth (`resources/views/patients/scan.blade.php`, `routes/web.php:99-100`). Camera scanning is `resources/js/scanner.js:273-314`.
3. The server writes a `PassportAccess` row with a method and an expiry (`app/Services/PassportAccessService.php`, schema at `database/migrations/2026_01_01_000005_create_encounters_and_access_tables.php:47-59`).
4. Read the summary first screen (`resources/views/patients/show.blade.php`).
5. Record a visit (`resources/views/clinical/encounter.blade.php`) or a vaccination (`resources/views/clinical/vaccination.blade.php`) or a reminder (`resources/views/clinical/reminder.blade.php`).
6. Close the passport (`routes/web.php:103`, `tests/Feature/PassportScanFlowTest.php:86-98`).

### 3.2 Registration and issuing a card

`routes/web.php:105-116`. Duplicate national ID is rejected, a unique passport number and QR token are generated (`app/Services/PassportNumberGenerator.php`, `app/Services/QrCodeService.php`). A child can only be linked to the mother through the mother's open passport (`tests/Feature/PassportScanFlowTest.php:144-159`). The card is printed from `resources/views/patients/card.blade.php:21` carrying the QR code.

### 3.3 Patient portal

`routes/web.php:132-139`. The holder sees their own passport, family members, the access log, a printable summary, and reminder preferences (`resources/views/portal/records.blade.php`, `resources/views/portal/summary.blade.php`). A child passport is reachable only through the mother (`tests/Feature/PassportExtrasTest.php:59-66`).

### 3.4 Facility administrator and system administrator

Facility admin manages health workers of their facility only and views reports and the facility scoped audit log (`routes/web.php:85-94`, `tests/Feature/PassportScanFlowTest.php:171-180`). System admin manages reference data, backups, and sees the full audit log (`routes/web.php:55-81`).

### 3.5 Offline path

The scan page is cached as one plain shell with no account and no patient data (`public/sw.js:15-30,54-64`, proven by `tests/Feature/OfflineVisitTest.php:40-46`). The worker stores the shell while installing, and the scan page primes both caches, each file stored on its own so one missing file cannot cost the rest (`resources/js/scanner.js:44-99`). Any navigation that cannot be reached offline falls back to that shell, and to a "No connection" page only when no shell is stored. A card read without a connection is queued in local storage (`resources/js/offlineQueue.js:48-60`). Notes can be typed against it (`resources/views/components/offline-queue.blade.php`). When the connection returns, the passport must still be opened by the scan first, then the notes are applied (`resources/js/scanner.js:120-152`, `tests/Feature/OfflineVisitTest.php:57-74`). The same visit sent twice is recorded once (`tests/Feature/OfflineVisitTest.php:87-97`).

---

## 4. Passport capability fit and scope drift

Judged against the nine required passport capabilities.

| Capability | Status | Evidence |
|---|---|---|
| 1. One patient, one passport, duplicate prevention, guardian link, unique number | Full | `database/migrations/2026_01_01_000003_create_patients_tables.php:13-15,33`, `PassportScanFlowTest.php:144-159` |
| 2. QR code, printable card, minimal public verify | Full | `patients/card.blade.php:21`, `certificates/verify.blade.php`, `routes/web.php:28-29` |
| 3. Patient portal | Full | `routes/web.php:132-139`, `portal/records.blade.php` |
| 4. Summary first one screen view | Full | `patients/show.blade.php`, `portal/records.blade.php` |
| 5. Visit captured once (registration, triage, consultation, lab, pharmacy handoff) | Partial | One `Encounter` only, no lab or pharmacy (`database/migrations/2026_01_01_000005_create_encounters_and_access_tables.php:12-43`) |
| 6. Immunisation against Malawi EPI with due and overdue | Partial | Schedule data exists, age logic not found in code (`database/migrations/2026_01_01_000009_create_vaccination_and_reminder_tables.php:14-19`) |
| 7. Consent, role based access, audit trail | Full | `portal/preferences.blade.php`, `PageAccessTest.php:41-61`, `database/migrations/2026_01_01_000012_create_audit_logs_table.php`, `routes/web.php:81` |
| 8. Continuity across visits and facilities | Full | `encounters.facility_id`, `vaccinations.facility_id`, access log per facility (`database/migrations/2026_01_01_000005_create_encounters_and_access_tables.php:15`, `database/migrations/2026_01_01_000009_create_vaccination_and_reminder_tables.php:26`) |
| 9. Resilience (printable, sync queue, graceful errors) | Full | `sw.js`, `offlineQueue.js`, `resources/views/errors/*` |

### 4.1 Classification of modules

Passport core (keep):
- Registration and card, scan first access, visit and vaccination record, patient portal, public certificate verify, offline support.

Supporting (keep, but simplify where noted):
- Accounts and role based access: keep.
- Facilities, districts, vaccine catalogue, settings: keep.
- Staff management: keep.
- Audit log: keep, it backs capability 7.
- Backups and system health: keep for operations, but they are admin overhead rather than passport function (`routes/web.php:71-77`).
- Reminders and notifications (portal, email, SMS, delivery status): simplify or hide. The copying of follow up and next dose into portal notifications is useful, but email and SMS fan out and per channel delivery records (`database/migrations/2026_10_04_000002_add_reminder_preferences_and_deliveries.php`) are more than a passport needs on a small site.
- Facility reports: simplify or hide. Reports are a management nicety, not one of the nine capabilities (`resources/views/facility/reports.blade.php`).

Drift (remove or replace):
- `docs/System Design & Requirements Document.md` is the drift artifact. It promises appointments, laboratory, consents, enrollment approval, NIN hashing, a verifier portal with accounts, and a super admin role, none of which exist. Recommend replacing it with `docs/system-design.md`, or rewriting it clearly as future scope. The code and `docs/system-design.md` agree with each other.

---

## 5. Efficiency versus paper

Measured as screens and clicks from sign in.

### 5.1 Find a returning patient

Paper: pull the folder by name. System: the card must be scanned. There is no search and no list (`tests/Feature/PageAccessTest.php:63-68` asserts `patients.index` must not exist). If the card is lost, the only fallback is passport number plus date of birth (`tests/Feature/PassportScanFlowTest.php:70-84`). On a weak connection the scanner first performs a 4 second reachability check (`resources/js/scanner.js:89-101`), which adds delay before the passport opens. This is a deliberate design (privacy by possession) but it is the biggest day to day friction for a paper trained worker.

### 5.2 Register a new patient

One form (`resources/views/patients/form.blade.php`), emergency contacts via a repeater (`resources/js/app.js:58-66`). The portal account is a separate action (`routes/web.php:108`). Fields are mostly typed once. Minor duplication: district and address are entered per patient with no reuse from a household.

### 5.3 Record a visit

Single screen with vitals grid, diagnosis, treatment, and a medicine repeater (`resources/views/clinical/encounter.blade.php`, `resources/views/components/offline-queue.blade.php:44-66`). Reason and diagnosis are required, and the offline draft form marks them required (`offline-queue.blade.php:24-25`), which matches the paper minimum. Defaults help: administered date defaults to today and follow up is forced to at least tomorrow (`resources/js/scanner.js:216-218`, `offline-queue.blade.php:27`).

### 5.4 Dispense medicine

Not covered. The system does not stock or dispense (`database/migrations/2026_01_01_000005_create_encounters_and_access_tables.php:34`). A pharmacy paper trail still runs in parallel. This is a scope decision, not a defect, but it means the passport does not yet remove the entire paper visit.

### 5.5 Check immunisation status

One screen shows the vaccination table with dose number, date given, and next dose (`resources/views/components/vaccination-list.blade.php`). Reminders show "Overdue since" (`resources/views/portal/records.blade.php:38`). However, there is no computed due or overdue flag for a vaccine based on the child's age. The health worker must still know the EPI schedule by heart. This is the clearest efficiency and safety improvement available.

### 5.6 Patient views their own record

Two clicks from the patient dashboard: "My passport card" and "Full passport" (`resources/views/dashboards/patient.blade.php:4-5`). Efficient.

### 5.7 Dead ends

No obvious dead ends in the visit flow. The main fallback path when a card is missing is discoverable but not obvious to a new worker.

---

## 6. Security, privacy, and error handling

### 6.1 Access control

Server enforced on every route group (`routes/web.php:54-139`). Policies back the passport reads (`app/Policies/PatientPolicy.php` with the open access check), and this is proven by `tests/Feature/PassportScanFlowTest.php:39-42` and `:86-98`. Administrators are blocked from passports (`PassportScanFlowTest.php:133-142`, `PageAccessTest.php:34-37`). Role isolation is tested in both directions (`PageAccessTest.php:41-61`).

Defense in depth note: `patients.show` and `patients.card` are registered without a permission middleware and rely only on the policy (`routes/web.php:110-112`). If the policy ever regresses, any authenticated user could attempt to read. This is acceptable but worth a defence in depth permission.

### 6.2 Passwords and sessions

Passwords are hashed, example rounds are 12 (`.env.example:17`). Login is throttled and passport opening is throttled (`routes/web.php:33`, `routes/web.php:100-101`). However the documented session defaults do not force HTTPS cookies and do not encrypt the session (`config/session.php:50`, `config/session.php:172`), and the example disables secure cookies by leaving `SESSION_SECURE_COOKIE` unset (`.env.example`). `APP_DEBUG=true` is the example default (`.env.example:4`). These are deployment settings, so the risk is realised only if the live `.env` keeps the example values. Flagged Medium.

### 6.3 Privacy of the holder

A passport is closed until the holder is present via a scan (`PassportScanFlowTest.php:39-42`). The holder sees who opened it and from where (`resources/views/portal/access-log.blade.php`, `resources/views/portal/records.blade.php:45-59`). The QR token is a bearer credential and is hidden from normal output, and the public verify page shows only the first name, vaccine, dose, date, and facility, not the last name (`tests/Feature/PassportExtrasTest.php:93-97`). SMS carries no health detail and needs consent (`tests/Feature/SmsNotificationTest.php:62-76`).

### 6.4 Offline privacy gap

The offline queue stores raw QR tokens and typed notes in local storage under `dhp.offline-queue` (`resources/js/offlineQueue.js:6,57`). Entries are scoped per worker id (`offlineQueue.js:45`) and expire after 30 minutes for a bare card or 24 hours once notes are added (`offlineQueue.js:30-31,34`). On sign out only the offline page cache is cleared; the queue is not (`resources/js/app.js:84-88`). On a shared device the tokens and notes therefore remain on disk until they expire, and a bare QR token can open the passport. Rated High. Fixes are cheap: clear `dhp.offline-queue` on logout, shorten the bare card lifetime, and consider encrypting queue entries.

### 6.5 Auditability

Important actions are written to `audit_logs` with user, facility, action, subject, and IP (`database/migrations/2026_01_01_000012_create_audit_logs_table.php`). System admin sees all, facility admin sees their facility (`routes/web.php:81`, `PageAccessTest.php:23-24`).

### 6.6 Backups

Files are encrypted with the application key and the test asserts the file contains no readable patient data (`tests/Feature/PassportExtrasTest.php:214-217`). Download requires the backup permission (`routes/web.php:71-76`). Restore is a destructive command with confirmation (`app/Console/Commands/RestoreBackup.php:30-31`). Retention keeps the newest three (`PassportExtrasTest.php:274-292`).

### 6.7 Error handling

Styled error pages exist for 403, 404, 419, 429, 500, and 503 (`resources/views/errors/`). The service worker falls back to the offline scan page, and shows a friendly "No connection" page only when no offline page is stored (`public/sw.js:40-46,54-64`). Offline validation failures send the worker back to the normal form with the text kept (`tests/Feature/OfflineVisitTest.php:99-112`). Good.

---

## 7. Severity rated findings

| # | Finding | Severity | Evidence |
|---|---|---|---|
| F1 | Offline queue not cleared on sign out, keeps QR tokens and notes on a shared device | High | `resources/js/app.js:84-88`, `resources/js/offlineQueue.js:6,57` |
| F2 | No age based immunisation due or overdue; `recommended_age` unused | Medium | `database/migrations/2026_01_01_000009_create_vaccination_and_reminder_tables.php:17`, no code found comparing it |
| F3 | Session not encrypted and secure cookies not forced in defaults | Medium | `config/session.php:50,172`, `.env.example:34` |
| F4 | `APP_DEBUG=true` example default | Medium | `.env.example:4` |
| F5 | No patient search or list; card is required, fallback is passport number plus DOB | Medium | `PageAccessTest.php:63-68`, `PassportScanFlowTest.php:70-84` |
| F6 | Requirements document describes an unimplemented larger system | Medium | `docs/System Design & Requirements Document.md` versus `PageAccessTest.php:63-76` |
| F7 | `patients.show` and `patients.card` rely on policy only, no permission middleware | Low | `routes/web.php:110-112` |
| F8 | No soft delete on patients; restore is destructive | Low | `database/migrations/2026_01_01_000003_create_patients_tables.php` (no softDeletes), `RestoreBackup.php:30-31` |
| F9 | Administrators have no passport access at all, so no break glass oversight | Low | `PassportScanFlowTest.php:133-142` |
| F10 | Pharmacy dispensing not covered, so a parallel paper trail remains | Low | `database/migrations/2026_01_01_000005_create_encounters_and_access_tables.php:34` |

---

## 8. Prioritised action plan and conclusion

Five changes, each with an effort estimate (S small, M medium, L large):

1. Clear the offline queue on sign out and reduce the bare card lifetime. Effort S. Add `dhp.offline-queue` removal alongside the existing shell cache delete in `resources/js/app.js:84-88`, and shorten `openMinutes` in `resources/js/scanner.js:34`. This closes the only High finding.
2. Add age based immunisation status. Effort M. Use the existing `recommended_age` and `days_between_doses` (`database/migrations/2026_01_01_000009_create_vaccination_and_reminder_tables.php:16-17`) to compute computed due and overdue in the vaccination view and the portal, so the schedule no longer has to be memorised.
3. Harden the documented production defaults. Effort S. In the deployment guide and `.env.example`, set `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, and consider `SESSION_ENCRYPT=true`. No code change needed.
4. Resolve the documentation drift. Effort S. Replace `docs/System Design & Requirements Document.md` with `docs/system-design.md`, or relabel it clearly as future scope.
5. Decide the intended product boundary for the supporting layer. Effort S to M. Either keep reminders, reports, and backups as they are, or hide facility reports and the email and SMS fan out for smaller deployments. Record the decision so the scope does not creep back toward a hospital system.

Conclusion. The passport concept is implemented faithfully and is well covered by tests: closed until scanned, holder can see every opening, minimal public verification, offline resilience, and a one screen summary. The system does not pretend to be a hospital management system, and the test suite actively guards that boundary. The work that remains is small and focused: close the offline shared device gap, add age based immunisation logic, tighten the production defaults, and make the documentation tell the truth about scope.
