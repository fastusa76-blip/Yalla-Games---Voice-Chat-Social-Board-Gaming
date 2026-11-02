# InfinityFree deployment checklist

## Before upload
- Import `database/schema.sql` into the MySQL database once.
- Copy `api/config.local.example.php` to `api/config.local.php` on your own computer.
- Fill in the exact MySQL hostname, database name, username and password from the InfinityFree control panel.
- Generate a private 64-character key in PowerShell:
  `[Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))`
- Put that key in `ADMIN_API_KEY` in your private `api/config.local.php` file. Do not upload it to GitHub.

## Upload these items into htdocs
- `index.html`
- `styles.css`
- `app.js`
- `.htaccess`
- `admin.html`
- `admin.js`
- Entire `api` folder, including `index.php` and `api/.htaccess`
- Your private `api/config.local.php` (created locally; it is intentionally not in GitHub)

## Do not upload
- `.github/`
- `tests/`
- `database/` (the SQL schema was imported through phpMyAdmin already)
- `deployment/`
- `README.md`, `BACKEND.md`, `LICENSE`

After upload, test:
`https://YOUR-DOMAIN/api/index.php?action=health`

Expected JSON includes:
`{"ok":true,"service":"yalla-games-api","database":"connected"}`

Do not treat the site as live until this endpoint and the homepage have both been tested.
