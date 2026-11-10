<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generated before the response is rendered so Blade (app shell and
        // error pages) can attach the same nonce to its inline <style> tags.
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        if (! property_exists($response, 'headers')) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        // The legacy XSS auditor is deprecated and its heuristics can introduce
        // new issues, so explicitly disable it instead of relying on browsers.
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($request, $nonce));

        // Laravel ships the CSRF token in a script-readable XSRF-TOKEN cookie
        // so Axios can mirror it into the X-XSRF-TOKEN header. The app reads
        // the token from a <meta> tag instead, so the cookie can be HttpOnly
        // (blocks JavaScript/session-hijacking access) without breaking CSRF.
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'XSRF-TOKEN' && ! $cookie->isHttpOnly()) {
                $response->headers->setCookie($cookie->withHttpOnly(true));
            }
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request, string $nonce): string
    {
        $policy = [
            "default-src 'self'",
            "script-src 'self'",
            // Inline <style> blocks (error pages, print/Word exports) carry a
            // per-response nonce. Style *attributes* (Vue :style bindings and
            // the print-export markup) are governed separately by CSP3's
            // style-src-attr, which cannot be nonced.
            "style-src 'self' 'nonce-".$nonce."'",
            "style-src-attr 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "frame-src 'none'",
            "form-action 'self'",
            "manifest-src 'self'",
        ];

        if (app()->environment('local')) {
            // IPv6 literals (e.g. http://[::1]:5173) are rejected by CSP
            // parsers, so Vite is pinned to 127.0.0.1 in vite.config.ts.
            $devOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
            $devConnect = array_merge($devOrigins, [
                'ws://localhost:5173',
                'ws://127.0.0.1:5173',
            ]);

            // The Vite dev server injects <style> elements at runtime, which
            // cannot carry the nonce; only relax the policy in local dev.
            $policy[2] = "style-src 'self' 'unsafe-inline' 'nonce-".$nonce."'";
            $policy[1] = "script-src 'self' ".implode(' ', $devOrigins);
            $policy[] = "connect-src 'self' ".implode(' ', $devConnect);
        } else {
            $connect = ["'self'"];

            if (($reverb = $this->reverbOrigin()) !== null) {
                $connect[] = $reverb;
            }

            $policy[] = 'connect-src '.implode(' ', array_unique($connect));
            $policy[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $policy);
    }

    /**
     * Reverb runs on its own port, which `'self'` does not cover. Allow only
     * the configured socket origin instead of every `ws:`/`wss:` host.
     */
    private function reverbOrigin(): ?string
    {
        $options = config('reverb.apps.apps.0.options', []);
        $host = $options['host'] ?? null;

        if (! is_string($host) || trim($host) === '') {
            return null;
        }

        $host = preg_replace('#^https?://#', '', trim($host));
        $scheme = ($options['scheme'] ?? 'https') === 'https' ? 'wss' : 'ws';
        $port = $options['port'] ?? null;

        $origin = $scheme.'://'.$host;

        if (is_numeric($port) && ! str_contains($host, ':')) {
            $origin .= ':'.$port;
        }

        return $origin;
    }
}
