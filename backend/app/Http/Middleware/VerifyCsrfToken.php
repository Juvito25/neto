<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * Las rutas que deberían estar exentas de protección CSRF.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/api/auth/register',
        '/api/auth/login',
        '/api/webhooks/*',
    ];
}
