# The Auto Hub Detailing Services

A responsive customer website with a PHP booking API, MySQL storage, and a staff dashboard. Includes nano coating, ceramic coating and headlight polishing; mobile layouts; an instant, rules-based service guide; booking validation; prepared SQL statements; CSRF protection; and hashed admin passwords. The service guide does not call an AI provider.

## Requirements

- PHP 8.2+ with PDO MySQL
- MySQL 8+ or compatible MariaDB
- XAMPP, or PHP's built-in server for local development

## Setup

1. Start MySQL. Import `database.sql` through phpMyAdmin, or run `mysql -u root -p < database.sql` in a shell supporting input redirection.
2. Copy `config.example.php` to `config.php`. Set your MySQL host, database, username and password. Use a dedicated database user in production.
3. Run `php tools/setup-database.php`, then `php tools/create-super-admin.php`. Enter your name, staff email and a password of 8–72 bytes. Password entry is visible in the terminal. This creates the initial Super Admin; future staff are managed in the staff portal.
4. From this directory run `php -S 127.0.0.1:8080`. Open http://127.0.0.1:8080. Staff login: http://127.0.0.1:8080/admin.php.

For XAMPP, place the project inside `htdocs/auto-hub`, start Apache and MySQL, then visit http://localhost/auto-hub/.

## Booking workflow

Customers submit their contact details, vehicle, service and preferred date. The request is stored as pending. Staff sign in to filter requests and change status to confirmed, completed or cancelled. Confirmation is manual; no emails or SMS are sent. Customer data is rendered with HTML escaping. No demo bookings, invented reviews or placeholder prices are used.

## Customer accounts and database creation

Open `/login.php` to sign in, or `/login.php?mode=register` to create an account. Customers receive a personal account page at `/account.php` with a booking shortcut and POST-based sign-out. Customer login is separate from staff login. Guest booking remains available; existing booking records are not claimed by matching email addresses. Password reset and email verification are not implemented.

Start MySQL from WampServer/XAMPP first. Copy `config.example.php` to `config.php`, update credentials locally, then run `php tools/setup-database.php`. This creates the `auto_hub` database and both `users` and `bookings` tables without deleting existing records. The database user needs CREATE privileges for this command. Existing installations can run the same command to add the users table.

The included `.htaccess` protects internal files on Apache when AllowOverride and mod_rewrite are enabled. Other servers require equivalent access rules. Session-level login throttling is included; public hosting should add server-level rate limiting.

## Before public launch

Add verified business contact details, address, actual pricing and your privacy policy. Replace the remote stock photograph with an owned business image if desired; Google fonts and the Unsplash image need internet access. Configure your business timezone in PHP. Serve through HTTPS and a proper PHP web server. Restrict web access to `config.php`, `config.example.php`, `database.sql`, `tools/`, `.git/` and README files, preferably keeping config outside the public document root. Add server-level login and booking rate limits (the included session limits are a local safeguard), backups and a customer-data retention policy. Never expose PHP's development server publicly.

## Project files

- `index.php`: customer website and request form
- `assets/`: responsive styles and frontend interactions
- `booking.php`: validated JSON booking endpoint
- `admin.php`: staff authentication, booking list and status updates
- `app.php`: database, session, CSRF and escaping helpers
- `database.sql`: database schema
- `config.example.php`: local configuration template
- `tools/password.php`: admin password hash generator

No framework installation or build step is required. The website renders before database configuration; submitting bookings requires a configured database. This project is delivered locally and has not been deployed.

## My appointments

Signed-in bookings are linked by `bookings.user_id` to the authenticated customer. Open `account.php#appointments` to view requests and refresh their status. Guests can still book, but earlier guest bookings are not automatically linked by email. Run `php tools/setup-database.php` on existing installations to add the nullable owner field, index and foreign key without changing existing bookings. Staff use the existing dashboard to update statuses.

With the local server running on port 8080, run `php tools/test-appointments.php` to verify account isolation, submission, safe note rendering and status updates. It creates temporary test users and bookings, then removes them.

## Admin and Super Admin

Both roles sign in at `/admin.php` with separate staff email/password credentials. Customer registration never grants staff access. The old shared `admin_hash` login is no longer used.

| Capability | Admin | Super Admin |
| --- | --- | --- |
| Overview and appointment counts | Yes | Yes |
| Search/filter appointments with pagination | Yes | Yes |
| Confirm, complete or cancel requests | Yes | Yes |
| View customer directory | Yes | Yes |
| Create staff and assign roles | No | Yes |
| Edit staff, reset passwords, deactivate/reactivate | No | Yes |
| Review activity log | No | Yes |

The migration has been applied locally. To activate the portal, run `php tools/create-super-admin.php` once and choose your own credentials. It refuses to run when staff already exist. Staff management then supports Admin and additional Super Admin accounts. You cannot disable/demote yourself or remove the final active Super Admin. Staff password/role changes invalidate older sessions. Accounts are deactivated rather than deleted, and staff changes and appointment status updates are audited. Staff login includes account and session attempt limits.

Run `php tools/test-staff.php` with the local server on port 8080 to verify role restrictions and management workflows. It creates and removes its own test data. Customer booking regression tests remain in `tools/test-appointments.php`.


## Owner SMS booking alerts

Each new booking and its SMS alert are saved in one database transaction. Alerts target `+94777278078` (0777 278 078). The message includes a booking reference, service and requested date, and asks staff to review the portal. SMS remains disabled until a provider is configured; no live delivery is claimed.

A Twilio adapter is prepared using its Messages API: https://www.twilio.com/docs/messaging/api/message-resource. If using another provider, replace the adapter before enabling. Copy the `sms` block from config.example.php into your local config.php, configure the provider account and SMS sender, set TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN and TWILIO_FROM_NUMBER in your PHP server environment, then enable SMS. Restart the PHP server after setting environment variables. Do not commit credentials. The sender/account must be allowed to message Sri Lankan numbers, and provider credit is required.

New bookings attempt dispatch when enabled. `php tools/send-booking-sms.php` processes up to 20 pending alerts, including earlier queued bookings; review the queue before running it. Accepted means the provider accepted the message, not that the phone received it. Failed or uncertain sends are not automatically retried to prevent duplicate charges/messages; inspect provider logs first. Bookings remain saved when delivery fails. The queue stores each attempt state in booking_sms; migration runs with `php tools/setup-database.php`.
"# The-Auto-Hub-Web" 
