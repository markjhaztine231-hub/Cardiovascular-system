# Security Audit — cardiovascularcare.site

- **Date:** 2026-11-09
- **Target:** `https://cardiovascularcare.site` (13.211.186.21, `ec2-13-211-186-21.ap-southeast-2.compute.amazonaws.com`)
- **Authorization:** Owner-directed test. Intensity: passive + low-impact only.
- **Constraints observed:** no form submissions, no credential attempts, no brute force, no writes, no sqlmap/nuclei active checks.
- **Tooling:** Drana-Infinity `88f3730`, Ollama 0.40.15 (Drana 1.5B and 7B), nmap 7.80, wafw00f 2.4.2, httpx, subfinder, webanalyze, waybackurls, curl, OpenSSL.
- **Artifacts:** `C:\Users\CE0163\AppData\Local\Temp\opencode\drana-stack\Drana-Infinity\results\`
- **Report status:** EVIDENCE items were directly observed from the internet. INFERRED items are marked and need server/DNS-console confirmation.

## 1. Evidence

| ID | Observation | Class |
|---|---|---|
| E1 | `22/tcp open ssh`, `80/tcp open http`, `443/tcp open https`; 997 ports filtered. | EVIDENCE |
| E2 | `Apache/2.4.66 (Ubuntu)` on 80/443; direct EC2 IP; wafw00f and nmap `http-waf*` found no WAF/CDN. | EVIDENCE (absence not provable) |
| E3 | Normal pages send CSP, HSTS (`max-age=31536000; includeSubDomains`), `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`. | EVIDENCE |
| E4 | CSP: `default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; connect-src 'self' ws: wss:`. | EVIDENCE |
| E5 | No `Cross-Origin-Opener-Policy`, `Cross-Origin-Embedder-Policy`, `Cross-Origin-Resource-Policy`, or `X-XSS-Protection` header. | EVIDENCE |
| E6 | `404` and `/up` responses carry none of the E3 headers. | EVIDENCE |
| E7 | `DELETE /` → `405` with an 842 KB Laravel Ignition debug page: `PHP 8.5.12`, `Laravel 12.69.2`, full vendor stack trace, request headers, route context. No CSP/XFO/HSTS on this response; inline style/script present. | EVIDENCE |
| E8 | `GET /_ignition/health-check` and `/_ignition/execute-solution` → `404`. | EVIDENCE |
| E9 | Cookies `XSRF-TOKEN` and session: `Secure`, `SameSite=Lax`; session also `HttpOnly`. | EVIDENCE |
| E10 | TLS 1.2 cipher list all grade A; TLS 1.3 negotiated (`TLS_AES_256_GCM_SHA384`); valid cert `CN=cardiovascularcare.site`, issuer SSL.com; client cipher preference. | EVIDENCE |
| E11 | `/.env`, `/.git/config`, `/storage/logs/laravel.log`, `/composer.json` → `404`; `/server-status` → `403`; `/build/` and `/.htaccess` → `403`; `/index.php` → `200`. | EVIDENCE |
| E12 | `/storage/` and `/storage/documents/` → `403` (directories exist); `/storage/app/public/` → `301` to itself without trailing slash. App writes uploads to the `public` disk (`Storage::disk('public')->url()`), files under `documents/`. | EVIDENCE + INFERRED (direct file fetch unverified) |
| E13 | Unauthenticated `/admin/dashboard`, `/doctor/dashboard`, `/patient/dashboard`, `/receptionist/dashboard`, `/documents/1`, `/profile`, `/doctor/pdf/1/echo` → `302 /login`. | EVIDENCE |
| E14 | `TRACE /` → `405`; `OPTIONS /` → `200 Allow: GET,HEAD`; `Origin: https://evil.example` gets no `Access-Control-Allow-Origin`; `http://` → `301 https://`. | EVIDENCE |
| E15 | DNS: SPF `v=spf1 include:spf.privateemail.com ~all`; `_dmarc` returns NXDOMAIN (no DMARC). | EVIDENCE |
| E16 | Upload validation in code: `mimes:jpg,jpeg,png,tiff,bmp,webp`, `max:10240`, random hashed names. Login throttle `30/min`; registration `5/10min`. | EVIDENCE (source) |

## 2. Findings

| ID | Severity | Confidence | Finding | Evidence | Remediation |
|---|---|---|---|---|---|
| F1 | **High** | High | Debug error page leaks framework/PHP versions, full stack trace, vendor paths, and request metadata to unauthenticated users. | E7 | Set `APP_DEBUG=false` on production, deploy with `composer install --no-dev` (remove Ignition), publish custom error views, run `php artisan config:cache`. ⚠ Confirm server `.env` / vendor set. |
| F2 | **Medium** | Medium | Uploaded medical scans are stored on Laravel's `public` disk, so `/storage/documents/<file>` bypasses the authenticated `documents/{document}` route if the path is known. Directory listing is disabled. | E12 | Move to a private disk and stream through an authorized controller/policy; or block `/storage/documents` at Apache and serve via signed URLs. ⚠ Confirm by fetching one known file. |
| F3 | **Medium** | High | SSH is reachable from the public internet on the EC2 instance; no WAF/CDN in front of the app. | E1, E2 | Restrict security-group ingress for 22 to admin IPs/VPN/SSM; key-only auth; consider CloudFront/WAF. ⚠ Confirm whether SSH is intentionally public. |
| F4 | **Low** | High | Security headers are absent on responses outside the `web` middleware group (404, `/up`, 405 error page). | E6, E7 | Set headers globally (middleware prepend or Apache `Header always set`), not only in the `web` group. |
| F5 | **Low** | High | CSP hardening gaps: `style-src 'unsafe-inline'`, broad `img-src https:`, `connect-src ws: wss:` in production, no COOP/CORP. | E4, E5 | Scope `img-src`/`connect-src` to required hosts; move styles to hashed assets or nonces; add COOP `same-origin` and CORP after testing. |
| F6 | **Low** | High | No DMARC record; SPF ends in `~all`. | E15 | Publish `_dmarc` (`p=none` → `quarantine` → `reject`) with `rua`, aligned with SPF/DKIM. |
| F7 | **Info** | High | Version disclosure: `Server: Apache/2.4.66 (Ubuntu)` and debug page (PHP/Laravel). | E2, E7 | `ServerTokens Prod`, `ServerSignature Off`; fixing F1 removes the rest. |
| F8 | **Info** | High | `/server-status` is reachable (403) and `/up` is public; `/robots.txt` disallows nothing. | E11, E6 | Block `mod_status` externally; keep `/up` only if monitoring needs it. |
| F9 | **Info** | High | Login throttle is 30/min per IP; no evidence of MFA enforcement. | E16 | Add per-account throttling and require 2FA for clinical/admin roles. |
| F10 | **Info** | High | HSTS has no `preload`; TLS cipher order is client-preferred. | E3, E10 | Add `preload` only after subdomain review; set server cipher preference. |

## 3. Controls Observed Working

| Control | Result |
|---|---|
| XSS defence in depth | Strict `script-src 'self'` (no `unsafe-inline`/`unsafe-eval`), `object-src 'none'`, `base-uri 'self'`. Inertia page data is HTML-escaped `data-page`, no inline scripts on the home page. |
| Clickjacking | `frame-ancestors 'none'` + `X-Frame-Options: DENY` on app pages. |
| MIME sniffing | `X-Content-Type-Options: nosniff`. |
| Transport | HTTP→HTTPS 301, valid cert, TLS 1.2/1.3 only, A-grade ciphers. |
| Session | `Secure`, `HttpOnly`, `SameSite=Lax`. |
| Auth gating | All tested portal/document/PDF routes redirect to `/login`. |
| CSRF/CORS/methods | No wildcard CORS; `TRACE` disabled; only `GET,HEAD` advertised. |
| Secrets exposure | `.env`, `.git`, logs, composer manifest all 404. |
| Upload validation | Image MIME allow-list, 10 MB cap, random names. |

## 4. Drana Run Notes (tool reliability)

| Drana scan | Result | Parser outcome |
|---|---|---|
| WAF (nmap + wafw00f) | No WAF; direct EC2 Apache | 1.5B **hallucinated “AWS WAF”**; 7B reported “No WAF” (correct). |
| PORTSCAN | `22, 80, 443` open | 1.5B **hallucinated “No open ports”**; 7B parsed correctly. |
| WEBTECH | Ubuntu, Apache 2.4.66, HSTS, Inertia.js | Correct. |
| SUBDOMAIN | No subdomains | Empty output. |
| GETURL | No archived URLs | Empty output. |
| Chat analysis (7B) | Unreliable at longer prompts: contradicted supplied evidence, recommended `X-XSS-Protection`, scored a correct 404 page as a finding | **Not used as a source of findings.** |

Conclusion: Drana is usable as an orchestration/collection front-end for its bundled tools, but its small local models are not trustworthy analysts. Raw tool logs were used for every finding above.

## 5. ⚠ CONFIRM MANUALLY

| Item | Where |
|---|---|
| `APP_DEBUG` value and whether `spatie/laravel-ignition` is installed | Production `.env`, `composer.json`, `vendor/` |
| Whether SSH/22 should be internet-facing | EC2 security group |
| Whether a WAF/CDN exists outside DNS inspection | AWS account |
| One known uploaded document is fetchable at `/storage/documents/<path>` without a session | Browser/curl with a real path |
| DMARC/SPF/DKIM alignment | DNS provider |
| Whether queue worker/scheduler run in production (not security, but affects OCR/reports) | EC2 process manager |

## 6. Out of Scope

- Authenticated/role-based testing (IDOR, privilege escalation, PHI access paths).
- Injection testing, fuzzing, file-upload abuse, credential attacks.
- Business-logic/audit-trail verification.
- Mobile/desktop clients, email infrastructure, cloud IAM.

## 7. Remediation Status (2026-11-09)

Implementation details and server commands are in `docs/security-hardening-2026-11-09.md`.

| ID | Status | Notes |
|---|---|---|
| F1 | Fixed in repo | Production forces `APP_DEBUG=false`; custom error views added. Deploy with `composer install --no-dev` to remove the debug renderer. |
| F2 | Fixed in repo | New scans use the private disk and an authenticated streaming route; `documents:move-to-private` migrates existing files. |
| F3 | Server action | ECG security group: restrict or remove public SSH/22. |
| F4 | Fixed in repo | `SecurityHeaders` is now global middleware. |
| F5 | Fixed in repo | `img-src` and `connect-src` narrowed; COOP/CORP/X-XSS-Protection added. `style-src 'unsafe-inline'` remains by design. |
| F6 | DNS action | Publish `_dmarc`; see hardening doc. |
| F7 | Repo + server | Version leak removed from error pages; set `ServerTokens Prod` / `ServerSignature Off`. |
| F8 | Server action | Block `mod_status`; decide whether `/up` stays public. |
| F9 | Fixed in repo (partial) | Per-email and per-IP login limits added. MFA enforcement still needs an enrolment decision. |
| F10 | Server action | HSTS preload only after subdomain review; server cipher preference. |
| Apache 2.4.66 | Server action | Upgrade to 2.4.68; if blocked, disable `mod_http2` immediately. See hardening doc. |
