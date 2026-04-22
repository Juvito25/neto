<?php

return [
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
    ],
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],
    'groq' => [
        'key' => env('GROQ_API_KEY'),
    ],
    'evolution' => [
        'url' => env('EVOLUTION_PUBLIC_URL', 'http://neto_evolution:8080'),
        'key' => env('EVOLUTION_API_KEY'),
    ],
    'mercadopago' => [
        'access_token' => env('MP_ACCESS_TOKEN'),
        'public_key' => env('MP_PUBLIC_KEY'),
        'webhook_secret' => env('MP_WEBHOOK_SECRET'),
    ],
];
