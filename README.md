# Laravel Teorion

[![Tests](https://github.com/teoprayoga/laravel-teorion/actions/workflows/tests.yml/badge.svg)](https://github.com/teoprayoga/laravel-teorion/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/teoprayoga/laravel-teorion.svg)](https://packagist.org/packages/teoprayoga/laravel-teorion)
[![Total Downloads](https://img.shields.io/packagist/dt/teoprayoga/laravel-teorion.svg)](https://packagist.org/packages/teoprayoga/laravel-teorion)
[![License](https://img.shields.io/packagist/l/teoprayoga/laravel-teorion.svg)](https://packagist.org/packages/teoprayoga/laravel-teorion)

Request-driven query filter package for Laravel — a formalized, secure replacement for ad-hoc query scope traits, with whitelist enforcement, isolated scope parameters, and single-call ViewModel integration.

## Features

- 🎯 **Declarative QueryFilter classes** — one per resource, central whitelist for filters/scopes/withs/sorts
- 🔒 **Whitelist enforcement** — no accidental scope/relation exposure to clients
- 🔐 **Isolated scope params** — `scopes[0][params][role_id]=3` keeps params scoped without colliding with global request
- 🔌 **Built-in filter types** — Exact, Like, MultiLike, Boolean, Null, Enum, In, Date, DateRange, Between, Range, GreaterThan, LessThan, Has, JsonContains, Callback, Scope
- 🔁 **Backward compatible** — supports legacy `scopes[]=name` format alongside the new isolated-params format
- 📑 **Dual sort format** — Spatie-style `?sort=-col,col2` AND legacy `?order_by=...&order_direction=...`
- 🗑️ **Auto soft delete handling** — `?with_trashed=1`, `?only_trashed=1` work automatically on SoftDeletes models
- 📊 **Aggregation support** — `withCount`, `withSum`, `withAvg`, `withMax`, `withMin` via `withAggregates[]`
- 📜 **Cursor pagination** — switch to cursor-based paging via `?pagination=cursor` for large datasets / infinite scroll
- 🔍 **Query audit & fingerprint** — deterministic SHA-256 fingerprint + `QueryAudited` event for observability, cache key derivation, and N+1 detection
- 🛠 **Fluent filter API** — `.alias()`, `.default()`, `.required()` chainable
- 🧰 **Macro system** — register custom global filter types via `FilterMacroRegistry`
- 📖 **Scribe integration** — auto-generate API docs from `#[UsesQueryFilter]` attribute
- ✅ **Validation rule generator** — `HasQueryFilterRules` trait auto-generates FormRequest rules

## Requirements

- **PHP** 8.1+
- **Laravel** 10, 11, 12, or 13

| PHP Version | Laravel 10 | Laravel 11 | Laravel 12 | Laravel 13 |
|:------------|:----------:|:----------:|:----------:|:----------:|
| 8.1         | ✅          | —          | —          | —          |
| 8.2         | ✅          | ✅          | ✅          | —          |
| 8.3         | ✅          | ✅          | ✅          | ✅          |
| 8.4         | —          | ✅          | ✅          | ✅          |

## Installation

```bash
composer require teoprayoga/laravel-teorion
```

(Service provider auto-discovers via Laravel package discovery.)

## Quick Start

### 1. Generate a QueryFilter class

```bash
php artisan make:query-filter PostQueryFilter
```

Creates `app/QueryFilters/PostQueryFilter.php`.

### 2. Declare your filters

```php
use Teoprayoga\Teorion\Filters\Filter;
use Teoprayoga\Teorion\QueryFilter;

class PostQueryFilter extends QueryFilter
{
    protected array $defaultSort = ['-created_at'];

    public function filters(): array
    {
        return [
            'search'     => Filter::multiLike(['title', 'description']),
            'status'     => Filter::enum('status', StatusEnum::class),
            'is_active'  => Filter::boolean()->default(true),
            'created_by' => Filter::exact(),
            'has_image'  => Filter::has('image'),
        ];
    }

    public function allowedScopes(): array
    {
        return ['published', 'popular'];
    }

    public function allowedWiths(): array
    {
        return ['author', 'comments', 'tags'];
    }

    public function allowedWithCounts(): array
    {
        return ['comments', 'reactions'];
    }

    public function allowedSorts(): array
    {
        return ['created_at', 'title', 'view_count'];
    }
}
```

### 3. Add the Filterable trait to your model

Three ways to bind a QueryFilter (pick one):

**A. Convention (zero boilerplate)** — model `Post` auto-resolves to `App\QueryFilters\PostQueryFilter`:

```php
use Teoprayoga\Teorion\Traits\Filterable;

class Post extends Model
{
    use Filterable;

    // Existing scopeXxx() methods stay here — whitelist controls which are exposed.
}
```

**B. Property override** — explicit, IDE-navigable:

```php
class Post extends Model
{
    use Filterable;

    protected string $queryFilter = CustomPostQueryFilter::class;
}
```

**C. Method override** — for dynamic resolution:

```php
use Teoprayoga\Teorion\QueryFilter;

class Post extends Model
{
    use Filterable;

    public function newQueryFilter(): QueryFilter
    {
        return new PostQueryFilter();
    }
}
```

Customize the convention namespace in `config/teorion.php`:

```php
'query_filters_namespace' => 'App\\Filters\\Query',
```

### 4. Use in your ViewModel/Controller

```php
public function index(GetRequest $request): mixed
{
    return Post::query()->filterAndPaginate($request);
    //                  ^^^^^^^^^^^^^^^^^^^^^^^^
    //                  applies all filters, scopes, withs, sorts,
    //                  and terminates with paginate() or get()
}

public function show(GetRequest $request, string $uuid): mixed
{
    return Post::findFiltered($request, $uuid);
}
```

## Request Format

| Param | Example | Effect |
|-------|---------|--------|
| Declared filter | `?search=lorem&status=published` | Each declared filter applied if param present |
| `scopes[]` legacy | `?scopes[]=published` | Calls `scopePublished($request)` with full request |
| `scopes[N]` new | `?scopes[0][name]=forStudent&scopes[0][params][role_id]=3` | Calls `scopeForStudent($scopedRequest)` with isolated params |
| `withs[]` | `?withs[]=author&withs[]=comments` | Eager loads (whitelist enforced) |
| `withCounts[]` | `?withCounts[]=comments` | Count loads (whitelist enforced) |
| `withAggregates` | `?withAggregates[comments][sum][]=score` | Sum/avg/max/min aggregates |
| `sort` | `?sort=-created_at,title` | Spatie-style sort, multi-column |
| `order_by` / `order_direction` | `?order_by=created_at&order_direction=desc` | Legacy single-sort |
| `is_paginate` | `?is_paginate=1&per_page=20` | Paginate vs get |
| `pagination` | `?pagination=cursor&per_page=20` | Cursor pagination mode (returns `CursorPaginator`) |
| `cursor` | `?cursor=eyJpZCI6MTB9` | Cursor token from previous response |
| `with_trashed` | `?with_trashed=1` | Auto-detected on SoftDeletes models |
| `only_trashed` | `?only_trashed=1` | Soft-deleted only |
| `visibles[]` / `hiddens[]` | `?hiddens[]=password` | makeVisible / makeHidden on result |

## Available Filter Types

| Filter | SQL |
|--------|-----|
| `ExactFilter` | `WHERE col = ?` |
| `LikeFilter` | `WHERE col LIKE %?%` |
| `MultiLikeFilter` | `WHERE (col1 LIKE %?% OR col2 LIKE %?%)` |
| `BooleanFilter` | `WHERE col = 1/0` |
| `NullFilter` | `WHERE col IS NULL` / `IS NOT NULL` |
| `EnumFilter` | `WHERE col = Enum::from(value)->value` |
| `InFilter` | `WHERE col IN (?, ?, ...)` |
| `DateFilter` | `WHERE DATE(col) = ?` |
| `DateRangeFilter` | `WHERE col BETWEEN ? AND ?` |
| `BetweenFilter` | `WHERE col BETWEEN ? AND ?` (from single param value array) |
| `RangeFilter` | `WHERE col >= ? AND col <= ?` (from `_min`/`_max`) |
| `GreaterThanFilter` | `WHERE col > ?` (or `>=`) |
| `LessThanFilter` | `WHERE col < ?` (or `<=`) |
| `HasFilter` | `WHERE EXISTS (relation)` / NOT EXISTS |
| `JsonContainsFilter` | `WHERE JSON_CONTAINS(col, ?)` |
| `CallbackFilter` | Inline closure |
| `ScopeFilter` | Delegates to existing `scopeXxx()` on model |

## Fluent API

```php
'search'     => Filter::multiLike(['name', 'desc'])->alias('q'),
'is_active'  => Filter::boolean()->default(true),
'tenant_id'  => Filter::exact()->required(),
```

## Macros (Custom Filter Types)

```php
// AppServiceProvider::boot()
FilterMacroRegistry::register('phone', function ($q, $value, $param) {
    return $q->where($param, preg_replace('/\D/', '', $value));
});

// In QueryFilter
'phone_number' => Filter::macro('phone'),
```

## Scribe Integration

```php
// Controller
use Teoprayoga\Teorion\Attributes\UsesQueryFilter;

class PostController
{
    #[UsesQueryFilter(PostQueryFilter::class)]
    public function index(GetRequest $request): JsonResponse { ... }
}
```

Register the strategy in `config/scribe.php`:
```php
'strategies' => [
    'queryParameters' => [
        Strategies\QueryParameters\GetFromInlineValidator::class,
        Strategies\QueryParameters\GetFromQueryParamTag::class,
        \Teoprayoga\Teorion\Scribe\Strategies\UsesQueryFilterStrategy::class,
    ],
],
```

## Validation Rule Generator

```php
use Teoprayoga\Teorion\Concerns\HasQueryFilterRules;

class GetRequest extends FormRequest
{
    use HasQueryFilterRules;
    protected string $queryFilter = PostQueryFilter::class;

    public function rules(): array
    {
        return array_merge($this->queryFilterRules(), [
            // your custom rules
        ]);
    }
}
```

## Configuration

`config/teorion.php` (published via `php artisan vendor:publish --tag=teorion-config`):

```php
return [
    'default_per_page'         => 10,
    'paginate_key'             => 'is_paginate',
    'per_page_key'             => 'per_page',
    'max_results_key'          => 'max_results',
    'pagination_mode_key'      => 'pagination',
    'cursor_pagination_value'  => 'cursor',
    'cursor_name'              => 'cursor',
    'query_filters_namespace'  => 'App\\QueryFilters',
    'strict_mode'              => env('APP_DEBUG', false),
    'audit' => [
        'enabled'     => env('TEORION_AUDIT_ENABLED', false),
        'log'         => env('TEORION_AUDIT_LOG', false),
        'log_channel' => env('TEORION_AUDIT_LOG_CHANNEL', null),
        'sample_rate' => (float) env('TEORION_AUDIT_SAMPLE_RATE', 1.0),  // 0.0–1.0
    ],
    'fingerprint' => [
        'algorithm'    => env('TEORION_FINGERPRINT_ALGORITHM', 'sha256'),  // sha256 | xxh3 | xxh128
        'exclude_keys' => ['_token', '_method', 'page', 'cursor', 'signature', 'expires'],
    ],
];
```

- `strict_mode=true` → throws `DisallowedScopeException` / `DisallowedWithException` / `ScopeMethodNotFoundException` on unlisted requests
- `strict_mode=false` → silently skips disallowed values (production-safe default)

## Cursor Pagination

For large datasets or infinite-scroll UIs, switch from offset to cursor pagination:

```
GET /posts?pagination=cursor&per_page=20
```

The response is a `CursorPaginator` exposing `next_cursor` / `prev_cursor` tokens. Pass via `?cursor=<token>` to navigate.

```php
// Filterable::scopeFilterAndPaginate return type
LengthAwarePaginator|CursorPaginator|Collection
```

Cursor pagination requires a deterministic `orderBy` clause — set one via `$defaultSort` in your `QueryFilter` (e.g., `protected array $defaultSort = ['-id'];`).

## Query Audit & Fingerprint

Enable structured audit for every `filterAndPaginate()` and `findFiltered()` call:

```env
TEORION_AUDIT_ENABLED=true
TEORION_AUDIT_LOG=true
TEORION_AUDIT_LOG_CHANNEL=stack
```

Listen for the event:

```php
use Teoprayoga\Teorion\Events\QueryAudited;

Event::listen(QueryAudited::class, function (QueryAudited $event) {
    // $event->record:
    //   fingerprint: ['hash', 'algorithm', 'payload']
    //   filter_class, model_class
    //   terminal_mode: 'paginate' | 'cursor' | 'collection' | 'find'
    //   limit, result_count, duration_ms, user_id
});
```

The **fingerprint** is a deterministic SHA-256 hash of `{filter_class, model_class, table, connection, normalized_parameters}`. Parameters are recursively `ksort`-normalized so order doesn't change the hash. Useful for:

- **Cache key derivation** — same intent → same hash → same cache entry
- **N+1 detection** — duplicate hashes within one request indicate suspicious repetition
- **Query analytics** — group slow queries by fingerprint to find optimization targets

Customize excluded parameters (defaults exclude page/cursor/CSRF tokens):

```php
'fingerprint' => [
    'exclude_keys' => ['_token', '_method', 'page', 'cursor', 'signature', 'expires', 'your_custom_key'],
],
```

### Fingerprint Algorithm Choice

The default is `sha256` — available without extensions, deterministic across PHP versions, fast enough (<1µs for typical request payloads). Hash itself is rarely the bottleneck — `json_encode` and recursive `ksort` dominate.

Three built-in algorithms (v2.4+):

| Algorithm | Hash length | Speed (relative) | Notes |
|-----------|------------|------------------|-------|
| `sha256`  | 64 hex | 1× (baseline) | Default. Crypto-grade, but overkill for fingerprinting |
| `xxh3`    | 16 hex | ~5–10× faster | PHP 8.1+ native via `hash()` |
| `xxh128`  | 32 hex | ~3–5× faster | Collision-resistant alternative to xxh3 |

Switch via config or env:

```php
'fingerprint' => ['algorithm' => 'xxh3'],
// or: TEORION_FINGERPRINT_ALGORITHM=xxh3
```

Switching invalidates existing fingerprints — derive cache keys with the algorithm prefix to make rotation safe: `"query:{$result->algorithm}:{$result->hash}"`.

Custom algorithms (e.g., blake3 via ext-blake3) can be registered in your `AppServiceProvider`:

```php
use Teoprayoga\Teorion\Fingerprint\AlgorithmInterface;
use Teoprayoga\Teorion\Fingerprint\AlgorithmRegistry;

AlgorithmRegistry::register(new class implements AlgorithmInterface {
    public function name(): string { return 'blake3'; }
    public function hash(string $payload): string { return hash('blake3', $payload); }
});
```

### Sampling

For production scale (10k+ req/min), full-rate audit can flood the event bus. Use `audit.sample_rate` (0.0–1.0):

```env
TEORION_AUDIT_SAMPLE_RATE=0.01   # audit 1% of queries
```

Sampling is **deterministic per fingerprint** — same query intent always produces the same audit decision (always-on or always-off). This is intentional: random sampling would break fingerprint-based cache dedup (same query might or might not be audited). Hash-based sampling keeps decisions consistent.

### Audit Boundaries

The audit hook is wired to these teorion terminal methods:

| Terminal | Audited? | `terminal_mode` |
|----------|----------|-----------------|
| `filterAndPaginate()` | ✅ | `paginate` / `cursor` / `collection` |
| `findFiltered()` | ✅ | `find` |
| `scopeFilter()` (raw Builder) | ❌ | — |
| `scopeFilterAudited()` (audited wrapper, v2.4+) | ✅ | `get` / `first` / `paginate` / `cursorPaginate` / `count` |
| Direct Eloquent calls | ❌ | — |

For audit on raw Builder chains, swap `filter()` → `filterAudited()`:

```php
Post::query()->filterAudited($request)->where('extra', $val)->get();
// → QueryAudited dispatched with terminal_mode='get'
```

### Listener Recipes

The package emits events; you decide persistence and side effects. Five patterns:

**1. Persist to database** — your own audit log table:

```php
class PersistQueryAudit
{
    public function handle(QueryAudited $event): void
    {
        QueryAuditLog::create([
            'fingerprint_hash' => $event->record['fingerprint']['hash'],
            'filter_class'     => $event->record['filter_class'],
            'duration_ms'      => $event->record['duration_ms'],
            'user_id'          => $event->record['user_id'],
            'recorded_at'      => now(),
        ]);
    }
}
```

**2. Slack alert on slow queries (>500ms):**

```php
Event::listen(QueryAudited::class, function (QueryAudited $event) {
    if ($event->record['duration_ms'] > 500) {
        Notification::route('slack', config('alerts.slack_webhook'))
            ->notify(new SlowQueryAlert($event->record));
    }
});
```

**3. Redis dedup cache (memoize identical queries):**

```php
Event::listen(QueryAudited::class, function (QueryAudited $event) {
    $key = 'query:' . $event->record['fingerprint']['hash'];
    Redis::setex($key, 60, json_encode($event->record));
});
```

**4. N+1 detection within request:**

```php
Event::listen(QueryAudited::class, function (QueryAudited $event) {
    static $seen = [];
    $hash = $event->record['fingerprint']['hash'];
    $seen[$hash] = ($seen[$hash] ?? 0) + 1;
    if ($seen[$hash] > 3) {
        Log::warning('Suspected N+1 — same query repeated', [
            'hash'  => $hash,
            'count' => $seen[$hash],
            'class' => $event->record['filter_class'],
        ]);
    }
});
```

**5. Sentry breadcrumb (low-overhead observability):**

```php
Event::listen(QueryAudited::class, function (QueryAudited $event) {
    \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
        level: 'info',
        type: 'query',
        category: 'teorion',
        message: "Query: {$event->record['filter_class']}",
        metadata: ['duration_ms' => $event->record['duration_ms']],
    ));
});
```

## Testing

```bash
composer test
```

## Support

If this package saves you time, consider supporting its development 🙏

[![Sponsor on GitHub](https://img.shields.io/badge/Sponsor-GitHub-EA4AAA?logo=githubsponsors&logoColor=white)](https://github.com/sponsors/teoprayoga)
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Buy%20me%20a%20coffee-FF5E5B?logo=kofi&logoColor=white)](https://ko-fi.com/teoprayoga)
[![Saweria](https://img.shields.io/badge/Saweria-Dukung-FAAB00?logoColor=white)](https://saweria.co/teoprayoga)

Maintained by **Teo Prayoga** ([@teoprayoga](https://github.com/teoprayoga)).

## License

MIT
