<?php

return [
    'default_per_page' => 10,
    'paginate_key'     => 'is_paginate',
    'per_page_key'     => 'per_page',
    'max_results_key'  => 'max_results',

    /*
     * strict_mode: true  → throw DisallowedScopeException / DisallowedWithException on unlisted values
     * strict_mode: false → silently skip unlisted values (production-safe)
     */
    'strict_mode' => (bool) env('APP_DEBUG', false),
];
