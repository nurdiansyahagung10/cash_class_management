<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vite;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $csp = implode(' ', [
            "default-src 'self' http: https: data: blob;",
            "script-src 'self' 'nonce-{$nonce}' https:;",
            "style-src 'self' 'unsafe-inline' https:;",
            "img-src 'self' data: blob: https:;",
            "font-src 'self' data: https:;",
            "connect-src 'self' https: wss:;",
            "object-src 'none';",
            "base-uri 'self';",
            "frame-ancestors 'self';",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
