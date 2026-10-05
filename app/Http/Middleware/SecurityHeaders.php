<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! property_exists($response, 'headers')) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($request));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request): string
    {
        $policy = [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
        ];

        if (app()->environment('local')) {
            // IPv6 literals (e.g. http://[::1]:5173) are rejected by CSP
            // parsers, so Vite is pinned to 127.0.0.1 in vite.config.ts.
            $devOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
            $devConnect = array_merge($devOrigins, [
                'ws://localhost:5173',
                'ws://127.0.0.1:5173',
            ]);

            $policy[1] = "script-src 'self' ".implode(' ', $devOrigins);
            $policy[] = "connect-src 'self' ".implode(' ', $devConnect);
        } else {
            $policy[] = "connect-src 'self' ws: wss:";
        }

        return implode('; ', $policy);
    }
}
