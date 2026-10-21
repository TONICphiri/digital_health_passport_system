# DPH Health Passport — Traffic Flow & Processing Performance Audit

Read-only analysis of `C:\Users\Adaline\Desktop\dph-system` followed by targeted,
low-risk fixes. Every finding cites the file that proves it. Query counts are
derived from the code paths, not from a profiler, because the application could
not be booted against the live database during this audit.

---

## 1. Executive summary

The system is a **server-rendered Laravel 12 monolith**. There is no API layer:
`routes/` contains only `web.php` and `console.php`, so every user action is a
full framework boot, session read, policy check, database round trip and Blade
render.

The dominant performance problem is **not** slow SQL. It is **query count
multiplied by network distance**:

- The application talks to **TiDB Cloud in `eu-central-1` over SSL**
  (`.env`, `config/database.php`). Every statement is a WAN round trip.
- The global view composer called the settings cache **three times per rendered
  component**, and a passport page renders 40–70 components. That produced an
  estimated **120–210 identical `cache` table queries per page view**.
- A single passport page re-ran the same `passport_accesses` lookup **about six
  times** through four Gate checks plus the view and dispensing policies.

Four changes remove almost all of that repeated work:

| Change | File | Effect |
|---|---|---|
| Settings memoised per request | `app/Services/SettingService.php` | 120–210 queries → 1 |
| Passport access memoised per request | `app/Services/PassportAccessService.php` | ~6 queries → 1 |
| Vaccine catalogue cached | `app/Models/Vaccine.php` | 1–3 queries → 0 |
| Date filters use index ranges | `app/Http/Controllers/DashboardController.php` | full scans → range scans |

Estimated result on the hottest page (`patients.show`): **~170–250 queries down
to ~40**, with no change to any security control.

---

## 2. How a request moves through the system

```
Browser / installed PWA
   │
   ├─ Static asset? ──────────────────────────────► served directly
   │                                                 (Vercel edge or Apache -f)
   └─ PHP request
        public/index.php
          │  (on Vercel: api/index.php first, which redirects storage to /tmp
          │   and overrides config/route/view cache paths)
          ▼
        bootstrap/app.php  →  Application::handleRequest()
          ├─ trustProxies(at: '*')
          ├─ EnsureAccountIsActive      → 1 facility lazy-load per request
          ├─ EnsurePasswordIsChanged
          ├─ StartSession               → session driver = database (remote query)
          ├─ VerifyCsrfToken
          ├─ Authenticate               → users query
          ├─ Spatie permission check    → roles query + cache-table read
          ├─ Route throttle             → cache-table read/write
          ▼
        Controller
          ├─ Gate / Policy
          │    └─ PassportAccessService::hasOpen()  → passport_accesses query
          │         (repeated once per policy call)
          ├─ Eloquent → MySQL protocol → TiDB Cloud (SSL, WAN)
          ├─ AuditLogger::record()     → synchronous INSERT
          ├─ NotificationDispatcher    → synchronous mail / SMS HTTP
          ▼
        Blade render
          ├─ View::composer('*')       → settings lookups per component
          ├─ 40–70 component instances
          └─ Response
```

### Route traffic profiles

| Class | Routes | Cost profile |
|---|---|---|
| Hot clinical path | `passports/open`, `patients/{patient}`, visit stage, dispense, vaccination | Highest. `patients.show` is the heaviest page. |
| Background polling | `GET notifications/unread` every 60 s per open tab | Constant low-value DB traffic. |
| Public verification | `verify/vaccination/{vaccination}` (signed, throttled) | Full boot with no session benefit. |
| Patient portal | `/my/records`, `/my/access-log`, `/my/summary` | `records` performs **writes on GET**. |
| Admin / analytics | dashboards, reports, audit log, system health | Aggregates over growing tables. |
| Offline sync | `POST passports/sync` | Extra HEAD + GET round trips to refresh the CSRF token. |

---

## 3. Findings, ranked by impact

### 3.1 Critical — settings re-read on every component

`app/Providers/AppServiceProvider.php` registers a composer for `'*'` and calls:

```php
$settings->systemName();
$settings->countryName();
$settings->issuingAuthority();
```

Each call reached `SettingService::all()`, which called
`Cache::rememberForever()`. Because the cache driver is `database`
(`CACHE_STORE=database`), each call was a real `SELECT` on the `cache` table —
not an in-process lookup.

`resources/views/patients/show.blade.php` alone contains 26 `<x-…>` tags, the
layout contains roughly 20 more, and loops render components repeatedly.
Realistically **40–70 view instances × 3 lookups = 120–210 queries per page**.

**Fix applied:** `SettingService` now keeps a `private ?Collection $memo`. The
service is already a singleton, so the first call reads the cache once and every
later call is an array lookup. `flush()` clears the memo as well as the cache.

### 3.2 Critical — all SQL crosses a WAN link

The database is TiDB Cloud in `eu-central-1`, reached over SSL. A query that
costs 0.2 ms locally costs 10–40 ms from another region. This is why **query
count** is the metric to optimise, and why the fix above has a disproportionate
effect.

**Recommendation (deployment):** run the PHP function in the same region as the
database (`vercel.json` → `regions`), enable persistent PDO connections, and
consider a connection pooler. Do this *after* reducing query count.

### 3.3 High — repeated passport-access checks

`app/Http/Controllers/Patients/PatientController.php` calls
`Gate::allows('recordStage', …)` once for each of the four stage types. Each call
enters `VisitPolicy`, which calls `PassportAccessService::hasOpen()`, which runs
the same `passport_accesses` query. The `view` check and `activeFor()` add more.

**Fix applied:** `PassportAccessService` now memoises `activeFor()` per
`userId:patientId`, is registered as a singleton so the controller and policies
share the memo, refreshes the memo in `open()`, and forgets it in `close()`.

### 3.4 High — everything is stored in the remote database

`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`.
That means sessions, permissions, settings, rate limiters, locks and queued jobs
all contend on the same remote MySQL server.

**Recommendation:** move cache and sessions to Redis (Upstash on Vercel). If that
is not possible, set `CACHE_STORE=file` at minimum; sessions may stay in the
database if they must survive serverless instances.

### 3.5 High — no compiled framework caches

`bootstrap/cache/` contains only `packages.php` and `services.php`. Config,
routes, views and events are rebuilt on every request.

**Recommendation:** add to the deploy step:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Routes contain no closures, so route caching is safe here.

### 3.6 Medium — `whereDate()` defeats indexes

`DashboardController` used `whereDate('opened_at', today())`, which wraps the
column in `DATE()` and prevents a range scan.

**Fix applied:** replaced with half-open ranges:

```php
->where('opened_at', '>=', $dayStart)
->where('opened_at', '<', $dayEnd)
```

Applied to `openedToday`, `visitsToday` and `issuedToday`, for both the facility
dashboard and the health-worker dashboard.

### 3.7 Medium — vaccine catalogue re-queried

`Vaccine::query()->where('is_active', true)->orderBy('name')->get()` appeared in
four places, including every patient page and the offline form data.

**Fix applied:** `Vaccine::activeCached()` uses
`Cache::rememberForever('vaccines.active')`, invalidated automatically by
`saved` and `deleted` model events, so an administrator's edit is visible at once.

### 3.8 Medium — notification polling

`resources/js/app.js` polled `notifications/unread` every 60 seconds per open
tab, including hidden tabs. Each poll is a full application boot.

**Fix applied:** the interval skips when `document.hidden`, and a
`visibilitychange` listener refreshes immediately when the user returns.

### 3.9 Medium — no queued work

`grep` for `ShouldQueue`, `dispatch` and `onQueue` in `app/` returned **zero
matches**, despite `QUEUE_CONNECTION=database`.

Consequences:

- SMS is a blocking HTTP POST with a 10-second timeout
  (`app/Services/Sms/TwilioGateway.php`).
- The nightly `reminders:send` walks every active patient and sends reminders
  serially.
- Manual backups run inside the web request: full-table `cursor()`, gzip level 6
  and encryption per 200-row block.

**Recommendation:** implement `ShouldQueue`, run a real worker, and move backups
to a scheduled CLI job writing to object storage.

### 3.10 Medium — missing composite indexes

Added in `database/migrations/2026_10_21_000001_add_performance_indexes.php`:

- `passport_accesses (facility_id, opened_at)`
- `audit_logs (facility_id, created_at)`
- `visit_stages (recorded_by, recorded_at)`
- `patients (registered_facility_id, created_at)`
- `notifications (notifiable_type, notifiable_id, read_at)`

### 3.11 Low — asset weight

40+ IBM Plex font subsets are emitted for Cyrillic, Greek and Vietnamese, and a
103 KB PNG is used as both favicon and sidebar logo.

**Recommendation:** import only the Latin font subsets, preload one display
face, replace the logo with SVG, and compress the banner image to WebP.

### 3.12 Low — development settings in `.env`

`.env` currently has `APP_ENV=local`, `APP_DEBUG=true`, `LOG_LEVEL=debug`. If
deployed as-is, every error renders a stack trace and nothing is cached by the
error handler. `.env.example` has already been hardened; the live file must be
set separately.

---

## 4. What was changed in this pass

| File | Change |
|---|---|
| `app/Services/SettingService.php` | Per-request memo for `all()`; `flush()` clears it |
| `app/Services/PassportAccessService.php` | Memoised `activeFor()` / `hasOpen()`; memo refreshed on open and cleared on close |
| `app/Providers/AppServiceProvider.php` | `PassportAccessService` registered as a singleton |
| `app/Http/Controllers/DashboardController.php` | `whereDate()` replaced by index-friendly ranges |
| `app/Models/Vaccine.php` | `activeCached()` with automatic invalidation |
| `app/Http/Controllers/Patients/PatientController.php` | Uses `Vaccine::activeCached()` |
| `app/Http/Controllers/Clinical/VaccinationController.php` | Uses `Vaccine::activeCached()` |
| `app/Support/OfflineForms.php` | Uses `Vaccine::activeCached()` |
| `app/Services/ImmunisationStatusService.php` | Uses `Vaccine::activeCached()` |
| `resources/js/app.js` | Polling paused on hidden tabs |
| `database/migrations/2026_10_21_000001_add_performance_indexes.php` | Composite indexes |

### Before → after on `patients.show`

| Query source | Before | After |
|---|---|---|
| Settings (view composer) | ~120–210 | 1 |
| Passport access checks | ~6 | 1 |
| Vaccine catalogue | 1–3 | 0 (cached) |
| Business queries | ~40 | ~40 |
| **Total** | **~170–250** | **~40** |

---

## 5. Why these fixes do not weaken security

Caching is a common source of security regressions, so each change was scoped
deliberately:

- **Settings memo is per request only.** It is not shared across users, and
  `flush()` clears it when an administrator saves.
- **Passport-access memo is per request and keyed by user *and* patient.** It is
  registered as a singleton so all policies see the same answer, but the
  application instance itself lives for one request. An access opened during the
  request updates the memo; a closed access removes it.
- **Vaccine cache contains no patient data** — only the national catalogue.
- **Indexes do not change row visibility**; policies still filter by facility and
  open access.
- The audit-log write, CSRF token, session regeneration and deactivation
  middleware are untouched.

---

## 6. How to verify the improvement

1. Install the new indexes:

   ```bash
   php artisan migrate
   ```

2. Confirm compiled caches in production:

   ```bash
   php artisan about
   ```

   Check that `config`, `routes` and `views` are cached after the deploy step.

3. Count queries on the hot page. In a local environment only, add this to
   `AppServiceProvider::boot()` temporarily:

   ```php
   if (app()->environment('local')) {
       \DB::listen(function ($query) {
           logger()->info('sql', [
               'ms' => $query->time,
               'sql' => $query->sql,
           ]);
       });
   }
   ```

   Load a passport page and count the `sql` entries, then compare with the
   ~170–250 baseline.

4. Verify correctness after the cache changes:
   - open a passport, record a stage, dispense a medicine;
   - edit a vaccine in the admin area and confirm it appears immediately;
   - close a passport and confirm access is refused on the next action;
   - deactivate a worker and confirm they are signed out on the next request.

---

## 7. Remaining priority list

**P0 (deployment)**
1. `php artisan config:cache route:cache view:cache event:cache`.
2. Move cache and sessions to Redis.
3. Co-locate PHP and TiDB in the same region.

**P1 (scalability)**
4. Queue SMS, email, reminder runs and backups.
5. Chunk `reminders:send` into per-patient jobs.
6. Move backups out of the web request to object storage.

**P2 (polish)**
7. Latin-only font subsets; SVG logo; WebP banner.
8. Paginate encounters and vaccination history on the passport page.
9. Cache the QR SVG per `qr_token` (the token is immutable).
10. Use `DB::table('audit_logs')->insert()` or a queued job for audit writes.

---

## 8. Conclusion

The system's processing cost is dominated by **repeated identical queries over a
WAN link**, not by expensive computation. Eliminating per-component settings
lookups and repeated access checks removes an estimated **75–80% of the queries
on the busiest page** without touching a single authorization rule. The next
gains come from deployment choices — compiled caches, Redis, regional
co-location and queued background work — rather than from rewriting clinical
logic.
