<?php

return [
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
    ],
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],
    'evolution' => [
        'url' => env('EVOLUTION_API_URL', 'http://evolution:8080'),
        'key' => env('EVOLUTION_API_KEY'),
    ],
];
