<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    public function handle($request, \Closure $next, ...$guards)
    {
        if ($this->isAuthenticated($request, $guards)) {
            return $next($request);
        }

        return $this->unauthenticated($request, $guards);
    }

    protected function isAuthenticated($request, array $guards)
    {
        if (empty($guards)) {
            return auth()->check();
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return true;
            }
        }

        return false;
    }

    protected function unauthenticated($request, array $guards)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect('/login');
    }
}