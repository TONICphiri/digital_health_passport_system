# Digital Health Passport

A web based health passport. Each person carries a card with a QR code. A health worker scans the card to open the passport, adds what happened at the visit, and the person can read their own passport and see who opened it.

It is not a hospital management system or a patient record system. It has no wards, beds, pharmacy stock, appointments or billing. It keeps only the essentials that should travel with a person from one facility to the next.

## Contents

- Roles
- How a visit works
- Running on a computer with XAMPP
- Demonstration accounts
- Running the tests
- Hosting on cPanel
- Design rules

## Roles

| Role | What the role does | What it cannot do |
|---|---|---|
| System Administrator | Registers facilities and facility administrators, manages the vaccine list, districts and settings, reads the activity log | See any passport |
| Facility Administrator | Registers the health workers of one facility, updates the facility profile, reads facility reports and activity | See any passport |
| Health Worker | Issues passports, opens a passport by scanning the card, records encounters, vaccinations and reminders | Manage accounts or settings |
| Patient | Reads their own passport and their children's, shows the QR card, sees who opened the passport | Change records |

## How a visit works

1. **New holder.** The health worker issues a passport. No card exists yet, so there is nothing to scan. The passport number and QR card are produced, and the passport is open for the worker who issued it.
2. **Every later visit begins with a scan.** The health worker opens "Open a passport" and scans the QR code on the card. Without the card, the passport number or National ID and the date of birth must match.
3. **The passport stays open for a limited time** (30 minutes by default, set in System settings). While it is open, and only then, the health worker can read it, record an encounter, record a vaccination and set reminders. It can be closed earlier.
4. **Every opening is recorded.** The holder sees who opened the passport, when, and how.
5. **Children** are linked through the mother's passport, which must be open on the same device.

## Running on a computer with XAMPP

Requirements: XAMPP with PHP 8.2 or newer, Composer, Node.js 18 or newer.

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open phpMyAdmin at http://localhost/phpmyadmin and create a database named `health_passport` with collation `utf8mb4_unicode_ci`.
3. In a terminal inside the project folder, run:

```bash
composer install
npm install
npm run build
copy .env.example .env        # on macOS or Linux: cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

4. Open http://localhost:8000 and sign in with one of the accounts below.

The `.env.example` file already uses the XAMPP defaults: user `root` with no password. Change `DB_USERNAME` and `DB_PASSWORD` if your MySQL is set up differently.

To start again with fresh demonstration data at any time:

```bash
php artisan migrate:fresh --seed
```

To run the daily reminder and age separation tasks by hand:

```bash
php artisan reminders:send
php artisan patients:separate-adults
```

## Demonstration accounts

Created when `SEED_DEMO_DATA=true`. Every account uses the password `Password@2026`.

| Role | Email | Belongs to |
|---|---|---|
| System Administrator | admin@healthpassport.mw | All facilities |
| Facility Administrator | facility@healthpassport.mw | Ndirande Community Hospital |
| Health Worker | healthworker@healthpassport.mw | Ndirande Community Hospital |
| Health Worker | healthworker2@healthpassport.mw | Ndirande Community Hospital |
| Patient | patient@healthpassport.mw | Grace Banda, mother of Daniel Banda |
| Facility Administrator | zomba.admin@healthpassport.mw | Zomba Central Hospital |
| Health Worker | zomba.healthworker@healthpassport.mw | Zomba Central Hospital |

## Running the tests

The tests use a separate database so your working data is never touched.

1. Create a second database named `health_passport_test`.
2. The tests connect with the MySQL user in `.env`. The test database name is set in `phpunit.xml`.
3. Run:

```bash
php artisan test
```

The tests cover page access for every role, the scan gate (a passport stays closed until the card is scanned, and closes again when it expires), the identity check used when a card is missing, recording encounters, linking a child through the mother's passport, and the access history shown to the holder.

## Hosting on cPanel

1. **Build locally.** Run `composer install --no-dev --optimize-autoloader` and `npm run build`.
2. **Upload.** Compress the project without `node_modules` and `.env`, upload it with File Manager to a folder outside `public_html`, for example `/home/USERNAME/health-passport`, and extract it.
3. **Point the domain to the public folder.** In cPanel, set the document root of the domain to `/home/USERNAME/health-passport/public`. If the document root cannot be changed, copy the contents of `public` into `public_html` and edit the two paths in `public_html/index.php` so they point to `../health-passport/vendor/autoload.php` and `../health-passport/bootstrap/app.php`.
4. **Create the database.** Use MySQL Databases in cPanel to create a database and a user, and give the user all privileges on the database.
5. **Create `.env`.** Copy `.env.example` to `.env` and set at least:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
DB_DATABASE=cpanel_database_name
DB_USERNAME=cpanel_database_user
DB_PASSWORD=strong_password
SEED_DEMO_DATA=false
ADMIN_EMAIL=your_admin_email
ADMIN_PASSWORD=a_strong_password
MAIL_MAILER=smtp
```

   Fill in the mail settings from the cPanel email account if you want email notifications.

6. **Finish the set up** from cPanel Terminal or SSH, inside the project folder:

```bash
php artisan key:generate
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

7. **Add the cron job.** In cPanel Cron Jobs, add one job that runs every minute:

```
* * * * * php /home/USERNAME/health-passport/artisan schedule:run >> /dev/null 2>&1
```

   This makes the database backup at 02:00, sends due reminders at 07:00 and separates adult records at 01:00 every day.

8. **Sign in** as the System Administrator, change the password on the profile page, then register the first facility and its administrator.

After any later update, run `php artisan migrate --force` and repeat the three cache commands.

## Backups

The System Administrator opens **Backups** to make a backup now, download any copy, or delete one. A copy is also made every day at the time set by `BACKUP_TIME` (default 02:00), and the administrators are notified when it finishes or fails. How many days copies are kept, and whether the daily backup runs, are on the Settings page. The newest three copies are always kept.

A backup file is compressed and encrypted with `APP_KEY`, so it cannot be read without that key. **Keep a copy of `APP_KEY` somewhere safe, away from the server.** A backup cannot be restored with a different key.

To restore on a working installation (same migrations, same `APP_KEY`):

```bash
php artisan backup:restore dhp-20261004-020000.dhpbak
```

To restore on a new server, install the project, copy the old `APP_KEY` into `.env`, run `php artisan migrate --force`, copy the backup file into `storage/app/private/backups/`, then run the command above. Restoring replaces all current data.

## Working without a connection

When the connection drops after the Open a passport page has loaded, or the page was opened once before while online, the health worker can still scan cards. Each card is kept in that browser, and visit notes or a vaccination can be typed for it. When the server can be reached again, the passport is opened by the scan first, then the notes are added, so the access log, permissions and validation work exactly as usual. If the notes break a rule, the normal form opens with the text kept so it can be corrected.

A card on its own is kept for the passport session time on the Settings page. Notes are kept for **Hours a visit typed offline is kept on the device** (24 by default), then removed. Nothing is kept for a different worker, and the offline page is removed from the device when the worker signs out. The device must have opened the Open a passport page online at least once after signing in.

Reading a passport offline is not possible by design: health information is never stored on the device.

## Text messages

Set `SMS_DRIVER` to `twilio` or `africastalking` and fill in that provider's values in `.env`, then switch on text messages on the Settings page. Messages are sent only to passport holders who agreed, and never contain clinical details. `SMS_DRIVER=log` writes messages to the log instead of sending them.

## Design rules

- Access is decided on the server by role permissions and by whether the passport is open.
#   d i g i t a l _ h e a l t h _ p a s s p o r t _ s y s t e m  
 