# Functionality audit — Digital Health Passport

Date: 21 October 2026
Commit audited: `52f7527` (`main`), built from `f106f06`
Scope: whether the system does what it claims, end to end — not a design review of the
UI, and not a repeat of `report.md` (workflow audit) or `docs/traffic-performance-audit.md`.

**Overall rating: 87 / 100 — production-capable, with five gaps worth closing before a
real facility depends on it.**

---

## 1. How this was tested

| Check | Result |
|---|---|
| Automated suite (`php artisan test`) | **71 passed, 352 assertions**, 27.7 s |
| Fresh install (`migrate:fresh --seed` on an empty SQLite database) | Passed — 20 migrations and 7 seeders ran clean |
| Operational commands (`reminders:send`, `patients:separate-adults`, `backup:run`, `schedule:list`) | All passed, including a real encrypted backup file |
| `route('…')` references in 187 PHP and Blade files | **0 broken** (93 named routes) |
| `view('…')` references | **0 missing** |
| Route → test traceability | 41 of 93 named routes are named in the suite (44 %) |
| Code-coverage percentage | Not measurable — no Xdebug or PCOV in the environment |
| `TODO` / `FIXME` / stub markers in application code | None |
| Endpoint smoke test of the newest features | Found and fixed one bug (§3, F‑01) |

Nine hundred lines of route/permission mapping, 19 models, 27 controllers, 23 services,
78 views and 7 test files were read or exercised.

---

## 2. Score breakdown

| Area | Weight | Score | Notes |
|---|---:|---:|---|
| Architecture and code quality | 10 | 9 | Enums, services, policies, form requests used consistently; one piece of dead code |
| Authentication, roles and permissions | 10 | 9 | Policies plus permission middleware; three routes rely on the policy alone |
| Patient registration and passport card | 8 | 8 | Duplicate detection, guardians, portal account creation, printed card |
| Scan, access control and audit trail | 10 | 10 | The strongest part of the system; see §4 |
| Visit workflow | 12 | 11 | Reception → triage → consultation → dispensing, locks, edits, vitals |
| Immunisation and reminders | 12 | 8 | Schedule is age-aware now, but a child who has had **no** dose is never reminded |
| Patient portal and privacy | 8 | 7 | Records, access log, printable summary, preferences |
| Facility and system administration | 8 | 7 | Complete, but almost entirely without automated tests |
| Offline and PWA | 6 | 5 | Offline scan queue, cached shell, install prompt; no background sync |
| Security and data protection | 10 | 9 | Encryption, masking, throttling, signed certificates; weak password policy |
| Testing, documentation and deployability | 6 | 4 | Strong core coverage, admin surface untested, two doc/deploy traps |
| **Total** | **100** | **87** | |

---

## 3. Findings

### F‑01 — Backup restore always reported failure (High) — **fixed in `52f7527`**

`BackupController::restore()` called `AuditLogger::record()` with an array as the third
argument, while the signature is `record(string, string, ?Model $subject)`. PHP throws a
`TypeError` before the method body runs, so the logger's own `try/catch` could not absorb
it; the controller's `catch (Throwable)` did, and returned **“The backup could not be
restored”** — after the database had in fact been restored. Every restore looked like a
failure, which invites an operator to restore the same file again.

Fixed by passing the row count in the description, and covered by
`tests/Feature/ReminderEditBackupRestoreTest.php`, which now asserts a success flash.

### F‑02 — A first dose is never chased (High, clinical)

`ImmunisationStatusService::assignedVaccines()` only treats a vaccine as the patient's own
once **one dose has been recorded**. `syncOverdueReminders()` uses that same list, so a
child who has received nothing generates no reminder at all — exactly the child most at
risk. The health worker's page still shows the vaccine as overdue, because
`PatientController@show` uses `allFor()` (the whole catalogue), so the clinician sees a
problem the family is never told about. The behaviour is deliberate and pinned by
`test_a_vaccine_that_was_never_given_creates_no_reminder`, which makes this a product
decision to revisit rather than a coding slip.

Suggested change: treat a vaccine as assigned when its `recommended_age_days` has been
reached, and keep “assigned, no dose yet” distinct from “given”.

### F‑03 — A new deployment silently sends nothing (Medium)

`.env.example` ships `MAIL_MAILER=log` and `SMS_DRIVER=log`. That is the right default for
local work, but the README's cPanel section does not call it out, so a facility can
configure a real SMS provider in Settings and still only write to `laravel.log`. The
reminder job reports “N reminders delivered” either way.

Suggested change: a one-line deployment checklist item, and have `SystemHealthController`
warn when mail or SMS is still on the `log` driver in production.

### F‑04 — Migration order is a deployment trap (Medium)

`2026_10_21_000002_encrypt_clinical_text_columns` converts existing rows to ciphertext.
The models now use `encrypted` casts, so **code deployed before the migration** throws
`DecryptException` when it reads any pre-existing clinical text. The migration is
idempotent and its `down()` decrypts, so the fix is documentation: migrations must run as
part of the same release, before traffic reaches the new code.

### F‑05 — 56 % of routes are untouched by tests (Medium)

No test names 52 of the 93 routes. The untested surface is concentrated in admin CRUD
(facilities, districts, facility administrators, staff accounts, vaccines, settings,
profile, notifications, password reset) — simple forms, but each is a write path with
validation and audit side effects. There is no coverage driver installed, so the 44 %
figure is traceability, not line coverage.

### F‑06 — Three sensitive routes have no permission middleware (Low)

`patients.show`, `patients.card` and `certificates.show` sit outside the `$can(...)`
groups and rely entirely on `$this->authorize()` in the controller. The policies do check
`OpenPassports` / `PrintPassportCard` / `ownsPatientRecord`, so **access is correct today**.
Adding the middleware anyway means a future controller edit cannot accidentally widen
access.

### F‑07 — Dead code (Low)

`Patient::scopeSearch()` is defined and never called. It is the only search scope in the
codebase; audit log and staff lists use their own queries.

### F‑08 — The audit log grows without limit (Low)

Backups have a retention setting (`backup_retention_days`, 30). The audit log — written by
32 call sites, including every sign-in, scan and certificate — has no pruning at all.
Pagination keeps the page fast, but the table is unbounded.

### F‑09 — Password policy is thin (Low)

`PasswordRule::min(8)->letters()->numbers()` — no mixed case, no symbol, no breach check.
Demo accounts all share `Password@2026`, which is printed in the README.

### F‑10 — Stale testing instructions (Low)

The README still tells the reader to create a MySQL `health_passport_test` database, but
`phpunit.xml` now runs SQLite `:memory:`. Following the README wastes time and hides the
fact that the suite needs no database server.

---

## 4. What is genuinely solid

- **The access model.** A passport is closed until the card is scanned or an identity check
  replaces it. `PassportAccessService` memoises one lookup per request, access expires,
  every open is logged, `patients.close` ends it, and `PatientPolicy` refuses reads without
  an open access record. Offline scans are replayed through the same rule.
- **Audit coverage.** 32 write paths log; `AuditLogger` swallows its own failures so a
  broken log can never block a clinician.
- **The visit state machine.** Stage positions come from config, a consultation with no
  medicines completes the visit immediately, dispensing remembers the pharmacist, a full
  visit locks, and edits are attributed rather than overwritten. All covered by tests.
- **Data protection.** Clinical free text is encrypted at rest, national IDs and passport
  numbers are masked to their last four characters on cards and printable summaries,
  backups are encrypted and asserted to contain no readable names or national IDs, and
  certificates are QR-verified through a
  **signed, throttled** public URL that reveals only vaccine, date and first name.
- **Abuse limits.** Login 5/min per email+IP, passport opening 20/min per user, password
  reset 6/min, certificate verification 60/min.
- **Recoverability.** Backup create, download, restore-from-list and restore-from-upload
  all work, and the restore validates the file format and refuses a truncated file
  (`finished` marker) rather than leaving half a database behind.

---

## 5. Resolved since the previous audit

Verified against the current code, not taken on trust:

| Previous gap | Current state |
|---|---|
| Offline queue survived sign-out | `resources/js/app.js` calls `clearAll()` on logout; bare cards expire after 5 minutes |
| No age-based immunisation schedule | `recommended_age_days` drives due/overdue with a configurable grace period |
| Insecure session/debug defaults | `.env.example` sets `APP_DEBUG=false`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true` |
| Reminders could not be edited | `reminders.edit` / `reminders.update` / `reminders.stop` exist and are tested |
| Backup could not be restored | Both restore paths exist and are tested |

Still open by design: there is no patient list or search. The card is the index.

---

## 6. What would move this to ~95

1. Create reminders for first doses (F‑02) — the single biggest clinical gap.
2. Add a production checklist covering migrate-before-deploy, cron, mail and SMS (F‑03, F‑04).
3. Test the admin CRUD surface, or at least the write paths that touch sensitive data:
   facilities, staff, settings, vaccines (F‑05).
4. Prune the audit log with a retention setting mirroring backups (F‑08).
5. Add `permission:` middleware to the three policy-only routes (F‑06).

Estimated effort: two to three days for items 1, 3 and 5; hours for the rest.

---

## 7. Verdict

For a card-first passport system whose whole point is “closed until scanned, and every
opening is accountable”, the core is trustworthy: the rule is enforced in the policy layer,
proven by tests, and visible to the patient in their own access log. Registration, the
visit workflow and recovery are complete. The deductions are real but narrow — a clinical
reminder rule that excludes the unvaccinated, an admin surface that no test exercises, and
two deployment footguns that a runbook would remove.

**87 / 100.**
