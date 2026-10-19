<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddLinkHeadersForPreloadedScripts
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! property_exists($response, 'headers')) {
            return $response;
        }

        $links = collect(Vite::preloadedAssets())
            ->reject(fn (array $attributes) => in_array('as="style"', $attributes, true))
            ->map(fn (array $attributes, string $url) => "<{$url}>; ".implode('; ', $attributes))
            ->join(', ');

        if ($links !== '') {
            $response->headers->set('Link', $links, false);
        }

        return $response;
    }
}
