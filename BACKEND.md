# Yalla Games PHP backend

## Requirements
- PHP 8.1 or newer
- MySQL 8.0+ (or compatible MariaDB)
- PHP extensions: PDO and pdo_mysql
- HTTPS enabled on the hosting account

## Install
1. Upload the site files and the `api/` directory to the same HTTPS web root.
2. Create a MySQL database and a dedicated database user with permissions only on that database.
3. Import `database/schema.sql` using the hosting control panel's database tool.
4. Set server environment variables: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `ADMIN_API_KEY`.
5. Generate a unique admin key of at least 32 random characters. Never put it in JavaScript, HTML, GitHub, or a public file.
6. Test `https://YOUR-DOMAIN/api/index.php?action=health`; it should return JSON with database connected.
7. Open `/admin.html`, enter the admin key, and review test orders.

## API
- `GET /api/index.php?action=health` — health/database check
- `POST /api/index.php?action=orders` — create a pending order
- `GET /api/index.php?action=order-status&reference=...&token=...` — private status lookup
- `POST /api/index.php?action=chat` — server-side FAQ chatbot response
- `GET /api/index.php?action=admin-orders` — authenticated order list
- `POST /api/index.php?action=admin-update` — authenticated status update; statuses: confirmed, rejected, fulfilled

## Order confirmation model
An order is always created as `pending`. Only an authenticated administrator can change it to `confirmed`, `rejected`, or `fulfilled`. Customers receive a reference and a private random token; only the token hash is stored in MySQL. A successful form submission means the request was saved, not that the business accepted it.

## Chatbot scope
The PHP chatbot is a deterministic FAQ assistant. It is not an LLM and does not hand off to a live agent. Connect a server-side AI provider later if desired; store provider keys only in hosting environment variables.

## Email
The email address is shown as a mail link. This release does not claim that an email was sent. Configure a reputable SMTP provider (with credentials kept server-side) before enabling transactional email.

## Security notes
- Keep HTTPS enabled.
- Keep the admin key private and rotate it if exposed.
- Use a dedicated database account; do not use the MySQL root account.
- Back up the database and apply host security updates.
- Configure PHP production settings to hide errors from visitors; server logs retain diagnostic details.
- Rate limits are stored in MySQL by a one-way hash of the requester IP and endpoint.
- Do not collect payment-card data on this site.
