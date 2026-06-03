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
    'default_per_page'        => 10,
    'paginate_key'            => 'is_paginate',
    'per_page_key'            => 'per_page',
    'max_results_key'         => 'max_results',
    'query_filters_namespace' => 'App\\QueryFilters',
    'strict_mode'             => env('APP_DEBUG', false),
];
```

- `strict_mode=true` → throws `DisallowedScopeException` / `DisallowedWithException` / `ScopeMethodNotFoundException` on unlisted requests
- `strict_mode=false` → silently skips disallowed values (production-safe default)

## Testing

```bash
composer test
```

## License

MIT
