<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class BaseFilter
{
    protected ?string $alias = null;
    protected mixed $defaultValue = null;
    protected bool $hasDefault = false;
    protected bool $required = false;

    public function __construct(protected ?string $column = null) {}

    abstract public function apply(Builder $query, mixed $value, string $param, Request $request): Builder;

    protected function resolveColumn(string $param): string
    {
        return $this->column ?? $param;
    }

    /**
     * Default validation rule for this filter type.
     * Subclasses override to provide more specific rules.
     */
    public function validationRule(): string|array
    {
        return 'nullable';
    }

    /**
     * Allow the client to pass a different param name than the declared key.
     *
     * Example:
     *   'search' => (new MultiLikeFilter(['name']))->alias('q')
     *   → client sends ?q=lorem, internally treated as 'search'
     */
    public function alias(string $alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Provide a default value applied when the client does NOT send the param.
     *
     * Example:
     *   'is_active' => (new BooleanFilter())->default(true)
     */
    public function default(mixed $value): static
    {
        $this->defaultValue = $value;
        $this->hasDefault   = true;
        return $this;
    }

    /**
     * Mark this filter as required.
     * Throws RequiredFilterMissingException if the param is absent.
     */
    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function getDefault(): mixed
    {
        return $this->defaultValue;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Resolve the request param to read from (alias takes precedence over declared key).
     */
    public function readKey(string $declaredKey): string
    {
        return $this->alias ?? $declaredKey;
    }
}
