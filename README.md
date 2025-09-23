# Subscription Web App — Setup & Documentation

This repository contains a subscription-based web application built with HTML, CSS, JavaScript, PHP, and MySQL. It integrates PayPal for subscription billing, enforces IP-based free registration limits, manages monthly usage limits, and hides Google Ads for paid subscribers.

The app is responsive (desktop + mobile), RTL-friendly, and uses a dark theme with a matching light mode.

---

## Features
- Authentication: Register (default Free), Login, Logout
- Plans & Billing (PayPal Subscriptions): Free, Basic Monthly, Basic Annual, Professional
- Ads logic: Google AdSense shown for Non-Logged-In + Free users only; auto-disabled for paid plans
- Usage Limits per month: videos and images counters with automatic monthly reset
- Webhooks: PayPal webhook to update user subscription status (activate, renew, cancel)
- Dashboards:
  - Subscriber: plan, usage, renewal countdown, quick upgrade link
  - Admin: KPIs, recent users, users list/search, revenue estimates, webhook logs
- IP-based free registration throttling (max N per IP/24h)
- Responsive, dark-themed UI with modals (Auth, Plans, Dashboards)

---

## Project Structure

- `index.html` — Main SPA-like page, top bar, modals (Auth, Plans, Dashboards), ad slots
- `css/subscribe.css` — Styles for modals, tabs, cards, buttons (built on theme tokens)
- `js/auth.js` — Frontend logic (modals, tabs, auth via Fetch, PayPal buttons, ads toggling)
- `translations.json` — I18N strings for UI texts (extend as needed)

- `php/config.php` — Reads environment variables, PayPal & DB config, plan ID maps
- `php/db.php` — PDO connection helper (`get_db()`)
- `php/helpers.php` — Response helpers, session, CSRF-ready foundation, admin checks
- `php/limits.php` — Limits per plan (videos/images/month, devices, resolution)
- `php/schema.sql` — Core DB schema (users, subscriptions, usage_counters, free_signup_log)
- `php/schema_add_webhook_logs.sql` — Adds webhook_logs table
- `php/schema_add_referrals.sql` — Adds referrals table (placeholder for future)

- `php/api/register.php` — Register new user (free plan), IP throttling, init usage row
- `php/api/login.php` — User login with password verification
- `php/api/logout.php` — Session logout
- `php/api/me.php` — Returns current user, usage (current month), latest subscription
- `php/api/usage/limits.php` — Returns plan limits + current usage
- `php/api/usage/increment.php` — Increments counters with transactional limit checks

- `php/api/admin/stats.php` — Totals & recent users (admin-only)
- `php/api/admin/users.php` — Users list/search (admin-only)
- `php/api/admin/revenue.php` — Revenue estimation by plan (admin-only)
- `php/api/admin/webhooks.php` — Paginated webhook logs (admin-only)

- `php/webhooks/paypal.php` — PayPal webhook listener with signature verification & logging
- `php/cron/reset_monthly.php` — Cron script to initialize current month usage rows
- `php/seed/create_admin.php` — One-time seeder to create the owner admin user

- `dashboard/subscriber.html` — Subscriber Dashboard (iframe’ed in modal)
- `dashboard/admin.html` — Admin Dashboard (iframe’ed in modal)

---

## Local Setup (Windows/Mac/Linux)

1. Install prerequisites
- PHP 8.x (with PDO MySQL extension)
- MySQL 8.x (or MariaDB)
- A local web server (Apache, Nginx, or PHP’s built-in server)

2. Create database
- Create a database and user in MySQL:
```sql
CREATE DATABASE appdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'appuser'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON appdb.* TO 'appuser'@'localhost';
FLUSH PRIVILEGES;
```

3. Import schema
- Import the SQL files in this order:
  - `php/schema.sql`
  - `php/schema_add_webhook_logs.sql`
  - `php/schema_add_referrals.sql`

4. Configure environment
- For local dev, you can set environment variables or hardcode temporary values in `php/config.php` (not recommended for production):
```
DB_HOST=localhost
DB_NAME=appdb
DB_USER=appuser
DB_PASS=strong_password_here
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=YOUR_SANDBOX_CLIENT_ID
PAYPAL_SECRET=YOUR_SANDBOX_SECRET
PAYPAL_WEBHOOK_ID=YOUR_SANDBOX_WEBHOOK_ID
ADMIN_EMAILS=your@email.com
```

5. Run a dev server
- Using PHP built-in server (from the project root):
```
php -S 127.0.0.1:8080
```
- Ensure the document root resolves `index.html` and that `php/` endpoints are accessible at `/php/...`.

6. Create admin user (one time)
- Visit: `http://127.0.0.1:8080/php/seed/create_admin.php`
- Response should say created or already exists.
- Delete `php/seed/create_admin.php` after running.

7. Test the app
- Open `http://127.0.0.1:8080/` in a browser.
- Register, login, open “Plans” and try PayPal buttons (sandbox).

---

## Hostinger Deployment

1. Deploy files
- Upload the entire project to `public_html/` (or the mapped doc root).

2. Create MySQL database
- hPanel → Databases → MySQL → create DB + user.
- Note down host, DB name, user, and password.

3. Set environment variables in Hostinger
- hPanel → Advanced → Environment Variables:
```
DB_HOST=...
DB_NAME=...
DB_USER=...
DB_PASS=...
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=AXgPoRNaqSHSwGjkABv89PBIkQxVwz-7ZCX5EoBkkG2UYqTYDhZ9W_3ajWWr17ij30QW7QLLBZIYbYve
PAYPAL_SECRET=EMmYmoDVGjvZeewKQK0cC1KdiL359YOD2HRDcx1y23tbfy1eUic6bmTqL5iNOzmPrNM1EkC5AslDmoQ4
PAYPAL_WEBHOOK_ID=1SE46005XX934033D
ADMIN_EMAILS=ads@4dads.pro
```

4. Import the database
- hPanel → phpMyAdmin → your DB → Import the three schema files.

5. Configure PayPal webhook
- Dashboard → Webhooks → Add webhook URL:
  - `https://YOUR_DOMAIN/php/webhooks/paypal.php`
- Select subscription-related events (activated, renewed, updated, created, canceled, suspended, expired).

6. Seed admin user (once)
- Visit: `https://YOUR_DOMAIN/php/seed/create_admin.php`
- Then delete the file.

7. Cron job for monthly usage reset
- hPanel → Advanced → Cron Jobs
  - Schedule: Day 1 each month at 00:05
  - Command:
```
php /home/USER/public_html/php/cron/reset_monthly.php
```

---

## PayPal Integration

- Frontend (`js/auth.js`)
  - Uses PayPal JS SDK (client ID loaded in `index.html` head).
  - Renders subscription buttons for paid plans.
  - Adds `custom_id` (user ID or email) to the subscription to help the webhook map the event to a user.

- Backend (`php/webhooks/paypal.php`)
  - Validates webhook signatures with PayPal (OAuth client credentials + `/verify-webhook-signature`).
  - On activation/renewal/creation: updates `users.plan_id` and inserts a `subscriptions` row.
  - On cancel/suspend/expire: downgrades to Free and inserts a `subscriptions` row.
  - Logs each webhook to `webhook_logs` with status.

- Plan IDs (live):
  - basic-monthly → `P-6ML527490D2009848NAFY5WA`
  - basic-annual → `P-6V5326030C814122HNAFZAFI`
  - professional → `P-5VS57764X8846254FNCLRMMA`

---

## Ads Logic (Google AdSense)
- AdSense client: `ca-pub-4300181789937712`
- Ads are shown only for:
  - Not-logged-in visitors
  - Logged-in users on `free` plan
- Ads are auto-hidden when the current user has any paid plan. Logic is in `js/auth.js:applyPlanUI()`.

---

## Security

- Password hashing: `password_hash()` (bcrypt/argon2 depending on PHP config)
- Sessions: PHP sessions are used for simplicity (cookie `appsid`). For production, consider rotating session IDs and using HTTPS only.
- CSRF: Endpoints accept JSON and are intended for same-origin `fetch` with session cookies. For strong CSRF protection, add an anti-CSRF token flow:
  - Generate a CSRF token server-side and store in session, expose via a small GET `/php/api/csrf.php` endpoint.
  - Require the token in a custom header for state-changing requests (`POST`/`PUT`/`DELETE`).
- JWT (optional): If you prefer JWT instead of PHP sessions, add login issuing JWT and verify on each API call. Keep JWT short-lived and refresh with rotation. Current implementation does not use JWT by default.
- Webhooks: PayPal webhook verification implemented via `/v1/notifications/verify-webhook-signature`.
- IP limits: `php/api/register.php` enforces per-IP throttling for free registrations (max 3 / 24 hours by default).

---

## Usage Limits
- Defined in `php/limits.php` by plan key.
- Enforced by `php/api/usage/increment.php` within a DB transaction and capped by plan limits.
- The cron script `php/cron/reset_monthly.php` ensures a zeroed row exists for each user/month.

---

## File-by-File Summary

- `index.html`
  - Top bar with buttons: Plans, Auth, Subscriber Dashboard, Admin Dashboard
  - Modals: Auth (Login/Register/Plans), Plans, Subscriber Dashboard (iframe), Admin Dashboard (iframe)
  - Google Ads slots (`#ads-home`), hidden for paid users

- `css/subscribe.css`
  - Modal, tabs, forms, plans grid/cards styling
  - Uses theme tokens from `:root` in `index.html`

- `js/auth.js`
  - Handles modal open/close, tab switching, login/register via Fetch (`/php/api/...`)
  - Syncs session (`/php/api/me.php`) and mirrors `user`/`plan` in localStorage
  - Renders PayPal buttons with `custom_id` and hides ads for paid plans
  - Shows dashboard buttons depending on login/admin

- `php/config.php`
  - Environment-based config for DB and PayPal
  - Maps PayPal plan IDs → internal plan keys

- `php/db.php`
  - `get_db()` for PDO MySQL connection

- `php/helpers.php`
  - JSON responses, sessions, auth helpers, admin check (`is_admin`) with owner email override

- `php/schema.sql`
  - `users`, `subscriptions`, `usage_counters`, `free_signup_log`

- `php/schema_add_webhook_logs.sql`
  - `webhook_logs` table (logs each webhook request)

- `php/schema_add_referrals.sql`
  - `referrals` table (foundation for future referrals feature)

- `php/api/*`
  - `register.php`, `login.php`, `logout.php`, `me.php`
  - `usage/limits.php`, `usage/increment.php`
  - `admin/stats.php`, `admin/users.php`, `admin/revenue.php`, `admin/webhooks.php`

- `php/webhooks/paypal.php`
  - Webhook validation and subscription state updates + logging

- `php/cron/reset_monthly.php`
  - Monthly initialization of usage rows

- `php/seed/create_admin.php`
  - One-time admin creation (remove after use)

- `dashboard/subscriber.html` & `dashboard/admin.html`
  - Lightweight pages rendered in modals via iframes

---

## API Quick Reference (Fetch)

- Register
```js
fetch('/php/api/register.php', {
  method: 'POST', headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'user@example.com', password: 'secret123' })
}).then(r=>r.json())
```

- Login
```js
fetch('/php/api/login.php', {
  method: 'POST', headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'user@example.com', password: 'secret123' })
}).then(r=>r.json())
```

- Me
```js
fetch('/php/api/me.php', { credentials: 'include' }).then(r=>r.json())
```

- Usage limits
```js
fetch('/php/api/usage/limits.php', { credentials: 'include' }).then(r=>r.json())
```

- Increment usage
```js
fetch('/php/api/usage/increment.php', {
  method: 'POST', headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ type: 'video', amount: 1 }),
  credentials: 'include'
}).then(r=>r.json())
```

---

## Theming & Responsiveness
- Theme tokens defined in `:root` (dark) and `.light-mode` (light)
- Touch targets use `--touch-target-min-size` (>= 44px)
- Modals and cards are responsive with CSS Grid/Flex and viewport-constrained sizes

---

## Roadmap / Notes
- JWT sessions: Current implementation uses PHP sessions. If you prefer JWT, add `login_jwt.php` to issue JWT and verify in a middleware. Keep refresh tokens secure.
- CSRF: Add anti-CSRF tokens for state-changing requests if the app grows beyond same-origin usage.
- Billing Portal: Add a PayPal billing portal link to the Subscriber Dashboard when available.
- Referrals: Complete the referrals feature (invite links, attributions, credits).

---

## License
This project is provided as-is. Review and adapt security, payments, and data processing to your jurisdiction and business requirements.
