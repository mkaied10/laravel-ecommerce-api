<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
{
    if (!auth()->check()) {
        return response()->json(['message' => 'Not authenticated'], 401);
    }
    if (!auth()->user()->is_admin) {
        return response()->json(['message' => 'Not an admin', 'is_admin' => auth()->user()->is_admin], 403);
    }
    return $next($request);
}
}
