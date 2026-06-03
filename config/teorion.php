<?php

return [
    'default_per_page' => 10,
    'paginate_key'     => 'is_paginate',
    'per_page_key'     => 'per_page',
    'max_results_key'  => 'max_results',
    'pagination_mode_key'      => 'pagination',
    'cursor_pagination_value'  => 'cursor',
    'cursor_name'              => 'cursor',

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

    'audit' => [
        'enabled'     => env('TEORION_AUDIT_ENABLED', false),
        'log'         => env('TEORION_AUDIT_LOG', false),
        'log_channel' => env('TEORION_AUDIT_LOG_CHANNEL', null),
        /*
         * Fraction of audit events to emit (0.0 = none, 1.0 = all).
         * Deterministic per fingerprint — same query intent always
         * produces same audit decision, consistent with cache derivation.
         */
        'sample_rate' => (float) env('TEORION_AUDIT_SAMPLE_RATE', 1.0),
    ],

    'fingerprint' => [
        /*
         * Hash algorithm name (registered in AlgorithmRegistry).
         * Built-ins: sha256 (default), xxh3, xxh128.
         * Switching invalidates existing fingerprints — invalidate
         * any cache derived from them, or prefix keys with $result->algorithm.
         */
        'algorithm'    => env('TEORION_FINGERPRINT_ALGORITHM', 'sha256'),
        'exclude_keys' => ['_token', '_method', 'page', 'cursor', 'signature', 'expires'],
    ],
];
