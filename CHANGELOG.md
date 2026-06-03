# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-06-03

### Added

- Convention-based `newQueryFilter()` auto-resolve in `Filterable` trait — model `Post` automatically resolves to `App\QueryFilters\PostQueryFilter` without manual implementation
- `$queryFilter` property override on models for explicit QueryFilter binding (`protected string $queryFilter = XxxQueryFilter::class`)
- `config('teorion.query_filters_namespace')` — configurable namespace for convention resolution (default: `App\\QueryFilters`)
- `QueryFilterNotFoundException` — thrown when neither property, method override, nor convention resolves a QueryFilter

### Changed

- `Filterable::newQueryFilter()` is no longer `abstract` — default implementation resolves by property → convention → exception
- Existing models with explicit `newQueryFilter()` method override continue to work unchanged (method dispatch wins over trait default)

### Breaking Changes

None. All resolution paths are additive; existing v2.0.x code works without modification.

---

## [2.0.0] - 2026-06-03

### Added

#### New Filter Types
- `BetweenFilter` — `WHERE col BETWEEN ? AND ?` from a single param value (array or comma-string)
- `RangeFilter` — `WHERE col >= ? AND col <= ?` reading separate `param_min` / `param_max` query keys
- `GreaterThanFilter` — `WHERE col > ?` or `WHERE col >= ?` (`orEqual` flag)
- `LessThanFilter` — `WHERE col < ?` or `WHERE col <= ?` (`orEqual` flag)
- `HasFilter` — `whereHas` / `whereDoesntHave` based on boolean value
- `JsonContainsFilter` — `WHERE JSON_CONTAINS(col, ?)`
- `CallbackFilter` — inline closure `(Builder, value, param, Request): Builder`

#### Fluent Filter API
- `BaseFilter::alias(string)` — map a request key to a different declared param
- `BaseFilter::default(mixed)` — apply filter even when key is absent from request
- `BaseFilter::required(bool)` — throw `RequiredFilterMissingException` when key is absent
- `BaseFilter::validationRule()` — return Laravel validation rule string/array for the filter

#### Sorting
- `SortResolver` — dual-format sort parsing:
  - Spatie-style: `?sort=-created_at,name`
  - Legacy single: `?order_by=created_at&order_direction=desc`
  - Legacy array: `?order[0][by]=created_at&order[0][direction]=desc`
  - Null-bottoms: `?order_null_bottoms[]=verified_at`
- `QueryFilter::$defaultSort` — default sort applied when client sends no sort params
- `QueryFilter::allowedSorts()` — whitelist for sortable columns
- Custom sort methods on QueryFilter: `sortBy{Column}(Builder, string): Builder`

#### Soft Delete Handling
- Auto-detect `SoftDeletes` trait via `class_uses_recursive` — no configuration needed
- `?with_trashed=1` → `withTrashed()`
- `?only_trashed=1` → `onlyTrashed()`
- `QueryFilter::$allowTrashedFilters` — set to `false` to disable per filter class

#### Aggregation Support
- `QueryFilter::allowedAggregates()` — declare which relations/operations are allowed
- Request format: `?withAggregates[relation][sum][]=column`, `[count]=1`, `[avg]`, `[max]`, `[min]`
- Pipeline applies `withSum`, `withAvg`, `withMax`, `withMin`, `withCount` accordingly

#### Scope Param Isolation
- `ScopedRequest` — isolated `Request` wrapping only scope-specific params
- New scope format: `?scopes[0][name]=forStudent&scopes[0][params][role_id]=3`
  - Scope receives `ScopedRequest` with only `role_id=3` visible
- Legacy format still supported: `?scopes[]=forStudent` (passes full `$request`)

#### Macro Registry
- `FilterMacroRegistry::register(string, Closure)` — global custom filter types
- `Filter::macro(string)` — resolve a registered macro as a `CallbackFilter`
- `FilterMacroRegistry::all()` / `has()` / `clear()` for testing

#### Scribe Integration
- `#[UsesQueryFilter(FilterClass::class)]` attribute for controllers
- `UsesQueryFilterStrategy` — Scribe strategy that auto-generates `@queryParam` entries
- `QueryFilterDocsExtractor` — extracts filter/sort/scope/with params from a QueryFilter class
- No hard dependency on Scribe — strategy works via `__invoke` convention

#### Validation Rule Generator
- `HasQueryFilterRules` trait for FormRequests
- Declare `protected string $queryFilter = MyQueryFilter::class`
- `queryFilterRules()` returns rules for all declared filters + base params (sort, withs, scopes, pagination)

#### Show Terminal
- `Filterable::findFiltered(Request, int|string): ?Model` — static method (not a scope)
  - Resolves by `id` (numeric) or `uuid` (string)
  - Applies `makeVisible` / `makeHidden` from request

#### DX
- `Filter` static factory class — `Filter::exact()`, `Filter::multiLike([...])`, `Filter::macro('name')`, etc.
- 69 tests, 142 assertions via Orchestra Testbench + SQLite in-memory
- `php artisan make:query-filter {Name}` artisan command
- Updated stub with all V2 methods pre-commented

### Changed

- `FilterPipeline` — now runs: soft deletes → filters → scopes → withs → withCounts → aggregates → sorts
- `Filterable::filterAndPaginate()` — auto-handles sort, soft delete, aggregates (was filtering + pagination only)
- `QueryFilter::apply()` — delegates to `FilterPipeline` (was inline logic)

### Breaking Changes

None. All V2 additions are opt-in. Existing V1 QueryFilter classes work without modification.

---

## [1.0.0] - 2025-01-01

### Added

- `QueryFilter` abstract base class
- `Filterable` trait with `scopeFilter()` and `scopeFilterAndPaginate()`
- Built-in filter types: `ExactFilter`, `LikeFilter`, `MultiLikeFilter`, `BooleanFilter`, `NullFilter`, `EnumFilter`, `InFilter`, `DateFilter`, `DateRangeFilter`, `ScopeFilter`
- `FilterPipeline` — applies filters, scopes, withs, withCounts
- `ScopeResolver` — whitelist-enforced scope execution
- `PaginationTerminal` — `is_paginate` + `per_page` + `makeVisible`/`makeHidden`
- `TeorionServiceProvider` with config publish + artisan command
- `config/teorion.php` with `default_per_page`, `strict_mode`, pagination keys
- `make:query-filter` artisan command
- `QueryFilterContract` interface
- Exceptions: `DisallowedScopeException`, `DisallowedWithException`, `ScopeMethodNotFoundException`
