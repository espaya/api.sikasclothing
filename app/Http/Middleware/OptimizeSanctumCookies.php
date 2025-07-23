<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptimizeSanctumCookies
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only optimize for API routes
        if ($request->is('api/*')) {
            // Remove unnecessary cookies from Sanctum responses
            foreach ($response->headers->getCookies() as $cookie) {
                if (!in_array($cookie->getName(), ['XSRF-TOKEN', 'laravel_session'])) {
                    $response->headers->removeCookie($cookie->getName());
                }
            }
        }

        return $response;
    }
}
