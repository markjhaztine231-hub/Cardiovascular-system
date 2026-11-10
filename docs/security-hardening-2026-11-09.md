# Security Hardening — 2026-11-09

- **Scope:** repository changes for the findings in `docs/security-audit-2026-11-09.md`.
- **Target deployment:** EC2 `/var/www/Cardiovascular-system`, Apache 2.4.66 (Ubuntu), PHP 8.5.
- **Status:** repository controls are implemented; infrastructure items are listed with exact commands and must be run on the server.

## 1. Repository Changes

| Finding | Change | Where |
|---|---|---|
| F1 debug leak | Production always forces `app.debug=false`, regardless of a stale `.env` or cached config. Custom 403/404/405/429/500/503 pages replace framework error pages. | `app/Providers/AppServiceProvider.php`, `resources/views/errors/` |
| F2 public scans | New scans are written to the private `local` disk. Files are served only by the authenticated `documents.file` route, which repeats the role/ownership check. A migration command moves existing files off the public disk. | `app/Models/Document.php`, `app/Http/Controllers/ReceptionistDocumentController.php`, `app/Jobs/ProcessDocumentOcr.php`, `app/Console/Commands/MoveDocumentsToPrivate.php`, `app/Http/Controllers/PdfController.php` |
| F4 headers bypassed | `SecurityHeaders` is global middleware, so 404s, `/up`, 405s and throttle responses carry the same headers as normal pages. | `bootstrap/app.php` |
| F5 CSP/COOP | `img-src` no longer allows every HTTPS host; production `connect-src` is `'self'` plus the configured Reverb origin instead of all `ws:`/`wss:`; added COOP, CORP and `X-XSS-Protection: 0`; added `frame-src 'none'`, `manifest-src 'self'` and `upgrade-insecure-requests`. | `app/Http/Middleware/SecurityHeaders.php` |
| F9 login throttle | Login now has a per-email limit (10/min) plus a per-IP limit (30/min), so a shared clinic IP cannot hide password guessing against one account. | `app/Providers/AppServiceProvider.php`, `routes/web.php` |

Deliberately unchanged:

- `style-src 'unsafe-inline'` remains because Vue scoped styles and the existing UI rely on inline style attributes. Tightening this needs a nonce/hash migration and is not a drop-in change.
- `X-XSS-Protection` is set to `0`, not `1`. The legacy auditor is deprecated and its heuristics can create new issues; CSP is the real anti-XSS control.

## 2. Deploy the Repository Changes

Run from `/var/www/Cardiovascular-system`:

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan migrate --force
```

Do **not** run `migrate:fresh`, `db:wipe`, or demo seeders in production.

### 2.1 Move existing scans to private storage

```bash
php artisan documents:move-to-private --dry-run
php artisan documents:move-to-private
```

The command:

- copies each referenced scan from `storage/app/public/documents` to `storage/app/private/documents`;
- verifies the private copy exists before deleting the public copy;
- also closes orphaned files still present under the public `documents/` directory;
- is idempotent and refuses to replace an existing private copy unless `--force` is passed.

After the move, direct requests to `/storage/documents/<file>` must no longer resolve. The authenticated route is `/documents/<id>/file`.

Ensure the PHP-FPM user can still write new scans to `storage/app/private/documents` (owned by the same user that runs the site; the project's usual `storage` permissions apply).

If the queue worker is running, restart it so the new disk code is loaded:

```bash
php artisan queue:restart
```

### 2.2 Verify

```bash
# Should return 404/403 and still carry security headers
curl -sSI https://cardiovascularcare.site/a-route-that-does-not-exist

# Should return 200 and carry security headers
curl -sSI https://cardiovascularcare.site/up

# Should redirect to /login or return 403, never the file bytes
curl -sSI https://cardiovascularcare.site/storage/documents/<known-file>

# CSP should no longer contain "ws:" / "wss:" or img-src https:
curl -sSI https://cardiovascularcare.site/login
```

Expected production CSP shape:

```text
default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';
img-src 'self' data: blob:; font-src 'self' data:; object-src 'none';
base-uri 'self'; frame-ancestors 'none'; frame-src 'none';
form-action 'self'; manifest-src 'self'; connect-src 'self';
upgrade-insecure-requests
```

## 3. Apache 2.4.66 Vulnerabilities

Apache 2.4.66 is affected by several high-severity issues, including a double-free in `mod_http2` (`mod_http2` CVE-2026-23918) that can allow unauthenticated remote code execution, plus later fixes in CVE-2026-42535 and CVE-2026-29170.

### 3.1 Required: upgrade

Upgrade to **Apache 2.4.68** (2.4.67 fixes the HTTP/2 issue; 2.4.68 includes later fixes):

```bash
sudo add-apt-repository ppa:ondrej/apache2
sudo apt update
apt policy apache2
sudo apt install --only-upgrade apache2
apache2 -v
sudo systemctl restart apache2
```

Confirm the running package, not just the binary path:

```bash
dpkg -l apache2 | tail -n 1
```

If the distribution repository still offers 2.4.66, the upgrade is blocked until the PPA or a newer Ubuntu image is used. Do not leave 2.4.66 exposed while waiting.

### 3.2 Interim: disable HTTP/2

If the upgrade cannot be completed immediately:

```bash
sudo a2dismod http2
sudo apache2ctl configtest
sudo systemctl restart apache2
apache2ctl -M | grep -i http2   # must be empty
```

This removes the critical `mod_http2` exposure. Clients fall back to HTTP/1.1.

### 3.3 Reduce exposed modules

Disable modules the app does not use, especially `dav_lock` and `proxy_ajp` if present:

```bash
apache2ctl -M | sort
sudo a2dismod dav_lock
sudo a2dismod proxy_ajp
sudo apache2ctl configtest
sudo systemctl restart apache2
```

Only remove modules after confirming they are not required (for example, do not remove `proxy`/`proxy_http` if a reverse proxy configuration needs them).

### 3.4 Version disclosure and status endpoints

In the global Apache configuration (`/etc/apache2/conf-available/security.conf` or equivalent):

```apache
ServerTokens Prod
ServerSignature Off
TraceEnable Off

<Location /server-status>
    Require all denied
</Location>
```

Then:

```bash
sudo systemctl reload apache2
curl -sSI https://cardiovascularcare.site/ | grep -i '^server:'
```

`Server` should report only `Apache`, without the version or OS.

## 4. Infrastructure Items

| ID | Action | Where |
|---|---|---|
| F3 | Restrict SSH/22 to the clinic IP, VPN, or AWS Systems Manager. Prefer SSM Session Manager and remove public `22` ingress entirely. | EC2 security group |
| F3 | Consider CloudFront + AWS WAF if the clinic needs a managed edge. The app currently has no WAF/CDN. | AWS |
| F6 | Publish a DMARC record. Start at `p=none` with reporting, then move to `quarantine` and `reject` after SPF/DKIM alignment is proven. | DNS |
| F8 | Keep `/server-status` blocked externally. Keep `/up` only if the monitoring system needs it; otherwise restrict it at Apache/security group. | Apache, monitoring |
| F10 | Add HSTS `preload` only after every subdomain is HTTPS-only. Set server cipher preference if the TLS policy requires it. | Apache TLS config |

Example DMARC starter record:

```dns
Name:  _dmarc.cardiovascularcare.site
Type:  TXT
Value: v=DMARC1; p=none; rua=mailto:dmarc@cardiovascularcare.site; fo=1
```

## 5. Remaining Decision

F9 recommends MFA for clinical and admin roles. The application already exposes Fortify two-factor challenge/enrolment, but enforcing it for every existing doctor/admin would lock out accounts that have not enrolled. Enable per-user MFA first, then enforce it for those roles in a separate change with an enrolment grace period.
