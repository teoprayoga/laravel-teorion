<?php

namespace Teoprayoga\Teorion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class QueryFingerprint
{
    public function make(QueryFilter $filter, Builder $query, Request $request): QueryFingerprintResult
    {
        $model = $query->getModel();

        $payload = [
            'filter_class' => $filter::class,
            'model_class'  => $model::class,
            'table'        => $model->getTable(),
            'connection'   => $model->getConnectionName() ?? $model->getConnection()->getName(),
            'parameters'   => $this->normalizedParameters($request),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return new QueryFingerprintResult(hash('sha256', $json), $payload);
    }

    private function normalizedParameters(Request $request): array
    {
        $parameters = $request->all();
        $exclude    = array_flip(config('teorion.fingerprint.exclude_keys', []));

        foreach (array_keys($parameters) as $key) {
            if (isset($exclude[$key])) {
                unset($parameters[$key]);
            }
        }

        return $this->sortKeys($parameters);
    }

    private function sortKeys(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortKeys($item);
            }
        }

        if (!$this->isList($value)) {
            ksort($value);
        }

        return $value;
    }

    private function isList(array $value): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
