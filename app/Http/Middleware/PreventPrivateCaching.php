<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages rendered for a signed-in user must not be kept in the browser cache: otherwise, after logging out,
 * the Back button shows the old page (password form, dashboard, reports) as if it were still accessible.
 */
class PreventPrivateCaching
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $wasSignedIn = $request->user() !== null;

        $response = $next($request);

        if ($wasSignedIn || $request->user() !== null) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
