# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.4.0] - 2026-06-03

### Added

#### Sample Rate
- `audit.sample_rate` config (float 0.0–1.0, default `1.0`) — controls fraction of queries audited
- **Deterministic sampling** via fingerprint hash modulo — same query intent always produces same audit decision (consistent with cache-key derivation, unlike `mt_rand`)
- Env: `TEORION_AUDIT_SAMPLE_RATE=0.01` for 1% sampling at scale

#### Audited Builder (close `scopeFilter()` audit gap)
- `Filterable::scopeFilterAudited($request)` returns `AuditedBuilder` wrapper
- Auto-emits `QueryAudited` event on terminal methods: `get`, `first`, `paginate`, `cursorPaginate`, `count`
- Enables audit for custom Builder chains: `Post::query()->filterAudited($request)->where(...)->get()`
- Audit Boundaries matrix in README updated — only direct Eloquent calls remain unaudited

#### Configurable Fingerprint Algorithm
- `Teoprayoga\Teorion\Fingerprint\AlgorithmInterface` + `AlgorithmRegistry` (extension point)
- Built-in algorithms: `sha256` (default, backward-compat), `xxh3` (~5–10× faster), `xxh128` (collision-resistant + fast)
- Config: `fingerprint.algorithm` (env: `TEORION_FINGERPRINT_ALGORITHM`)
- Custom algorithms registerable via `AlgorithmRegistry::register()` (e.g., blake3 with ext-blake3)
- `QueryFingerprintResult.algorithm` field exposes the algo used — derive cache key as `"query:{$result->algorithm}:{$result->hash}"` for safe algorithm switching

### Refactored

- `Filterable` — extracted private helpers `shouldAudit(QueryFingerprintResult)` and `emitAudit(array)` to DRY audit dispatch across `auditFilteredQuery`, `auditFindResult`, and `AuditedBuilder`

### Verified

- **96 tests, 183 assertions** pass (83 + 3 sample rate + 5 audited builder + 5 algorithm registry) on PHP 8.1–8.4 × Laravel 10–13 CI matrix

### Breaking Changes

None. All defaults unchanged: sample_rate=1.0 (100% audit), algorithm=sha256 (existing hashes valid).

---

## [2.3.1] - 2026-06-03

### Documentation

- README: "Why SHA-256?" rationale for default fingerprint algorithm
- README: "Audit Boundaries" matrix — explicit table of which terminals are audited
- README: "Listener Recipes" — 5 listener patterns (DB persist, Slack alert, Redis dedup, N+1 detection, Sentry breadcrumb)

### No Code Changes

This is a documentation-only patch release. v2.3.0 consumers can upgrade safely without testing.

---

## [2.3.0] - 2026-06-03

### Added

#### Cursor Pagination
- `PaginationTerminal` supports `cursorPaginate()` via request `?pagination=cursor`
- New method: `PaginationTerminal::resolveMode(Request)` returns `'cursor'|'paginate'|'collection'`
- New method: `PaginationTerminal::resolveLimit(Request)` extracts per-page/max-results value
- New config keys: `pagination_mode_key`, `cursor_pagination_value`, `cursor_name`
- `Filterable::scopeFilterAndPaginate()` return type widened to `LengthAwarePaginator|CursorPaginator|Collection`

#### Query Audit & Fingerprint
- `Teoprayoga\Teorion\Events\QueryAudited` — event dispatched after every `filterAndPaginate()` or `findFiltered()` call when audit is enabled
- `Teoprayoga\Teorion\QueryFingerprint` — deterministic SHA-256 fingerprint service that normalizes request parameters (sorted keys, excluded pagination tokens)
- `Teoprayoga\Teorion\QueryFingerprintResult` — readonly DTO: `hash`, `payload`, `algorithm`
- New config block: `audit.enabled`, `audit.log`, `audit.log_channel`, `fingerprint.exclude_keys`
- Audit record includes: `fingerprint`, `filter_class`, `model_class`, `terminal_mode`, `limit`, `result_count`, `duration_ms`, `user_id`
- `findFiltered()` also emits audit with `terminal_mode = 'find'`

#### Integration
- `HasQueryFilterRules` includes validation rules for new `pagination` and `cursor` query params
- `QueryFilterDocsExtractor` documents `pagination` and `cursor` params for Scribe auto-gen

### Verified

- 84 tests, 165+ assertions pass on Laravel 10–13 stacks
- Audit disabled by default — zero overhead for v2.2.x installs

### Breaking Changes

None. All additions are opt-in via config flags.

---

## [2.2.0] - 2026-06-03

### Added

- GitHub Actions CI workflow — automated tests on PHP 8.1–8.4 × Laravel 10–13 matrix (11 combinations)
- README badges — CI status, Packagist version, downloads, license
- Requirements compatibility table in README

### Changed

- `composer.json` — expanded `illuminate/database`, `illuminate/http`, `illuminate/support` constraints to `^10.0|^11.0|^12.0|^13.0`
- `composer.json` — expanded `orchestra/testbench` to `^8.0|^9.0|^10.0|^11.0`
- `composer.json` — expanded `phpunit/phpunit` to `^10.0|^11.0|^12.0`

### Verified

- 74 tests, 147 assertions pass on Laravel 10, 11, 12, and 13 stacks
- Zero code changes required for Laravel 12 / 13 compatibility — package APIs were already version-agnostic

### Breaking Changes

None. Constraint expansion is additive; existing v2.1.x installs unaffected.

---

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
