<?php

namespace Teoprayoga\Teorion\Filters;

use Teoprayoga\Teorion\Macros\FilterMacroRegistry;

/**
 * Static factory for built-in filter types and macros.
 * Lets you write declarative filters in a fluent way:
 *
 *   'search'  => Filter::like('name'),
 *   'phone'   => Filter::macro('phone'),
 */
class Filter
{
    public static function exact(?string $column = null): ExactFilter
    {
        return new ExactFilter($column);
    }

    public static function like(?string $column = null): LikeFilter
    {
        return new LikeFilter($column);
    }

    public static function multiLike(array $columns): MultiLikeFilter
    {
        return new MultiLikeFilter($columns);
    }

    public static function boolean(?string $column = null): BooleanFilter
    {
        return new BooleanFilter($column);
    }

    public static function null(?string $column = null): NullFilter
    {
        return new NullFilter($column);
    }

    public static function enum(?string $column = null, ?string $enumClass = null): EnumFilter
    {
        return new EnumFilter($column, $enumClass);
    }

    public static function in(?string $column = null): InFilter
    {
        return new InFilter($column);
    }

    public static function date(?string $column = null): DateFilter
    {
        return new DateFilter($column);
    }

    public static function dateRange(?string $column = null, string $fromParam = 'start_date', string $toParam = 'end_date'): DateRangeFilter
    {
        return new DateRangeFilter($column, $fromParam, $toParam);
    }

    public static function between(?string $column = null): BetweenFilter
    {
        return new BetweenFilter($column);
    }

    public static function range(?string $column = null): RangeFilter
    {
        return new RangeFilter($column);
    }

    public static function gt(?string $column = null, bool $orEqual = false): GreaterThanFilter
    {
        return new GreaterThanFilter($column, $orEqual);
    }

    public static function lt(?string $column = null, bool $orEqual = false): LessThanFilter
    {
        return new LessThanFilter($column, $orEqual);
    }

    public static function has(string $relation): HasFilter
    {
        return new HasFilter($relation);
    }

    public static function jsonContains(?string $column = null): JsonContainsFilter
    {
        return new JsonContainsFilter($column);
    }

    public static function callback(\Closure $callback): CallbackFilter
    {
        return new CallbackFilter($callback);
    }

    public static function scope(string $scopeName): ScopeFilter
    {
        return new ScopeFilter($scopeName);
    }

    /**
     * Resolve a previously registered macro filter.
     */
    public static function macro(string $name): CallbackFilter
    {
        return new CallbackFilter(FilterMacroRegistry::get($name));
    }
}
