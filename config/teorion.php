<?php

return [
    'default_per_page' => 10,
    'paginate_key'     => 'is_paginate',
    'per_page_key'     => 'per_page',
    'max_results_key'  => 'max_results',

    /*
     * Namespace used to auto-resolve QueryFilter classes from model names.
     * Example: Post model → {namespace}\PostQueryFilter
     * Override per-model via $queryFilter property or newQueryFilter() method.
     */
    'query_filters_namespace' => 'App\\QueryFilters',

    /*
     * strict_mode: true  → throw DisallowedScopeException / DisallowedWithException on unlisted values
     * strict_mode: false → silently skip unlisted values (production-safe)
     */
    'strict_mode' => (bool) env('APP_DEBUG', false),
];
