# DEPLOY.md

This document describes how to deploy the Kofar Arziki product repository to a production or development environment. It includes required environment variables, database import instructions, configuration notes, and a testing checklist.

IMPORTANT
- Do not commit secrets or API keys to the repository. Use environment variables or your hosting panel's secure settings.
- Keep `SECURE_COOKIE = true` in production and ensure your site runs on HTTPS.

1) Required environment variables

Set the following environment variables in your hosting environment (cPanel, Docker, systemd, etc.). For local testing you may set them in a local `.env` file used by your CI deployment scripts (do NOT commit `.env`).

- DB_HOST - Database host (e.g., 127.0.0.1)
- DB_PORT - Database port (default 3306)
- DB_USER - Database username
- DB_PASS - Database password
- DB_NAME - Database name

- APP_ENV - `development` or `production` (sets error reporting behaviour)
- SESSION_NAME - session cookie name (optional)
- SESSION_TIMEOUT - session timeout seconds (e.g., 3600)
- SECURE_COOKIE - `true` in production (requires HTTPS). For local HTTP testing set `false`.
- HTTPONLY_COOKIE - `true` recommended

VTU/API & Providers (production only)
- VTU_TEST_MODE - `true` to simulate VTU calls for testing; set `false` in production.
- VTU_API_BASE_URL - VTU base url
- VTU_API_KEY - VTU API key (production)
- VTU_API_SECRET - VTU API secret (production)

Payment gateway (if used)
- PAYMENT_GATEWAY - e.g., `paystack` or `flutterwave`
- PAYSTACK_PUBLIC_KEY - (if using Paystack)
- PAYSTACK_SECRET_KEY - (if using Paystack)

Email / SMTP (if used)
- SMTP_HOST
- SMTP_PORT
- SMTP_USERNAME
- SMTP_PASSWORD
- SMTP_FROM_EMAIL
- SMTP_FROM_NAME

SMS Provider (if used)
- SMS_PROVIDER
- SMS_API_KEY
- SMS_API_URL

2) File & config notes
- `config.php` reads environment variables using `getenv()`. For cPanel, set environment variables in the _Advanced_ settings or use a protected `.env` read by the deployment script.
- Keep `APP_ENV` as `production` on a live site and `development` only on local/dev environments.

3) Database setup

- Create the database if it doesn't exist (example using MySQL):

  ```bash
  mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS kofar_arziki CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  ```

- Import the schema (run from project root or provide an absolute path to `database.sql`):

  ```bash
  mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p $DB_NAME < database.sql
  ```

  Note: `database.sql` contains `CREATE TABLE IF NOT EXISTS` statements, but always backup your live DB before importing.

4) Optional migration: migrate legacy `users.wallet_balance` to the canonical `wallet` table

- Use the provided `migrations/migrate_wallets.sql` script to safely migrate balances for users that do not already have a `wallet` row.

  ```bash
  mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p $DB_NAME < migrations/migrate_wallets.sql
  ```

  This script is idempotent; it will not duplicate wallet rows.

5) Permissions & directories

- Ensure the following directories exist and are writable by the webserver (create if missing):
  - `logs/`
  - `uploads/`
  - `cache/`

6) Deploying to cPanel

- Upload the repository to your cPanel file manager or use Git deployment on cPanel (preferred). Ensure the repository's files are placed under the `public_html` (or the correct domain folder).
- Set the environment variables in cPanel (Advanced -> Environment Variables) and enable HTTPS via cPanel (Let's Encrypt or your certificate).
- Ensure `BASE_URL` in `config.php` matches your domain (or set via env var `BASE_URL`).

7) Local development (quick setup)

- Set a `.env` file (only for local dev, do not commit):
  ```env
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_USER=root
  DB_PASS=secret
  DB_NAME=kofar_arziki
  APP_ENV=development
  VTU_TEST_MODE=true
  SECURE_COOKIE=false
  ```

- Import `database.sql` and run the site (e.g., via XAMPP, MAMP or built-in PHP server):
  ```bash
  php -S 127.0.0.1:8000
  ```

8) Testing checklist (Register → Login → Dashboard → Airtime/Data → Logout)

- Register a test user via `/register.php`.
- Login via `/login.php`.
- Add funds to wallet for testing using SQL if you don't have a deposit flow:
  ```sql
  UPDATE wallet SET balance = 2000.00 WHERE user_id = <id>;
  ```
- Purchase airtime via `/airtime.php` (when `VTU_TEST_MODE=true` the purchase is simulated).
- Purchase data via `/data.php` (simulated if `VTU_TEST_MODE=true`).
- Logout via `/logout.php`.
- Inspect database tables: `users`, `wallet`, `transactions` for expected rows.

9) Troubleshooting

- If cookies / login do not work, confirm `SECURE_COOKIE` flag matches whether you're testing over HTTPS.
- If you see CSRF errors, ensure you have sessions configured and the `csrf_token` is included in forms.
- Check `logs/` for application logs (config.php sets `logs/error.log` by default).

10) Security recommendations

- Use HTTPS on production.
- Store API keys and DB credentials as environment variables (never commit them).
- Regularly rotate keys and enforce least privilege on the DB user.

---

If you'd like, I can also add a small `DEPLOY.sh` script to assist with importing the DB and performing basic checks — tell me and I will include it in the branch.