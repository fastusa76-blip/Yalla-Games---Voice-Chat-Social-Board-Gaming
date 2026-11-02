# Yalla Games PHP backend

## Requirements
- PHP 8.1 or newer
- MySQL 8.0+ (or compatible MariaDB)
- PHP extensions: PDO and pdo_mysql
- HTTPS enabled on the hosting account

## InfinityFree / shared-hosting installation
1. Upload the public website files to the hosting web root: `index.html`, `styles.css`, `app.js`, `.htaccess`, `admin.html`, `admin.js`, and the complete `api/` directory.
2. In InfinityFree, create a MySQL database and import `database/schema.sql` once using phpMyAdmin.
3. Copy `api/config.local.example.php` to `api/config.local.php` on your computer. Edit the copy with the exact database hostname, database name, database username, and database password shown by your hosting control panel.
4. Generate a unique admin key with at least 32 random characters. Keep it private. Put it in `ADMIN_API_KEY` in `api/config.local.php`. Never commit or share this file.
5. Upload `api/config.local.php` into the server's `api/` folder. The repository .gitignore excludes this file. The API also supports server environment variables as a fallback for local/CI deployments.
6. Enable HTTPS and visit `https://YOUR-DOMAIN/api/index.php?action=health`. A successful response contains `"database":"connected"`.
7. Open `/admin.html` and enter the same admin key to review orders.

Example PowerShell command to generate a 64-character hexadecimal admin key locally:
```powershell
[Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
```
Do not paste the generated key into chat, GitHub, HTML, or JavaScript.

## Files not required on the live web root
The `.github/`, `tests/`, and `database/` folders are not part of the public website upload. Keep `database/schema.sql` locally as a backup/reference after importing it. `README.md`, `BACKEND.md`, `LICENSE`, and `deployment/` are documentation, not required at runtime.

## API
- `GET /api/index.php?action=health` — health/database check
- `POST /api/index.php?action=orders` — create a pending order
- `POST /api/index.php?action=order-status` — private status lookup (reference and token are sent in the JSON body)
- `POST /api/index.php?action=chat` — server-side FAQ chatbot response
- `GET /api/index.php?action=admin-orders` — authenticated order list
- `POST /api/index.php?action=admin-update` — authenticated status update; statuses: confirmed, rejected, fulfilled

## Order confirmation model
An order is always created as `pending`. Only an authenticated administrator can change it to `confirmed`, `rejected`, or `fulfilled`. Customers receive a reference and a private random token; only the token hash is stored in MySQL. A successful form submission means the request was saved, not that the business accepted it.

## Chatbot scope
The PHP chatbot is a deterministic FAQ assistant. It is not an LLM and does not hand off to a live agent. Connect a server-side AI provider later if desired; store provider keys only in hosting environment variables or the private PHP config.

## Email
The email address is shown as a mail link. This release does not claim that an email was sent. Configure a reputable SMTP provider (with credentials kept server-side) before enabling transactional email.

## Security notes
- Keep HTTPS enabled.
- Keep the admin key private and rotate it if exposed.
- Keep `api/config.local.php` out of GitHub and public downloads.
- Use a dedicated database account; do not use the MySQL root account.
- Back up the database and apply host security updates.
- Configure PHP production settings to hide errors from visitors; server logs retain diagnostic details.
- Rate limits are stored in MySQL by a one-way hash of the requester IP and endpoint.
- Do not collect payment-card data on this site.
