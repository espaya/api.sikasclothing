<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return response()
                ->header('Access-Control-Allow-Origin', 'http://localhost:3001')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-XSRF-TOKEN, Authorization')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->setStatusCode(200);
        }

        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:3001');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}
