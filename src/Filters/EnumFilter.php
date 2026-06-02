<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EnumFilter extends BaseFilter
{
    public function __construct(
        ?string $column = null,
        private readonly ?string $enumClass = null,
    ) {
        parent::__construct($column);
    }

    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        $col = $this->resolveColumn($param);

        if ($this->enumClass !== null) {
            // Spatie Enum: StatusEnum::from($value)->value
            if (method_exists($this->enumClass, 'from')) {
                try {
                    $resolved = $this->enumClass::from($value);
                    $value    = $resolved instanceof \BackedEnum ? $resolved->value : $resolved->value;
                } catch (\ValueError) {
                    return $query->whereRaw('1 = 0');
                }
            }
            // Native PHP backed enum
            elseif (is_subclass_of($this->enumClass, \BackedEnum::class)) {
                try {
                    $value = $this->enumClass::from($value)->value;
                } catch (\ValueError) {
                    return $query->whereRaw('1 = 0');
                }
            }
        }

        return $query->where($col, $value);
    }

    public function validationRule(): string|array
    {
        if ($this->enumClass && is_subclass_of($this->enumClass, \BackedEnum::class)) {
            $values = array_map(fn($case) => $case->value, $this->enumClass::cases());
            return 'nullable|in:' . implode(',', $values);
        }

        return 'nullable|string';
    }
}
