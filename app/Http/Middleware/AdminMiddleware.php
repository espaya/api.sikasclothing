<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'ADMIN') {
            // Redirect to home or abort with 403
            // return abort(403, 'Unauthorized access - Admins only');
            return response()->json(['message' => 'Unauthorized Access'], 403);
        }

        return $next($request);
    }
}
