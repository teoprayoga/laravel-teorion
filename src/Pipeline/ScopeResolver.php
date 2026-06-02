<?php

namespace Teoprayoga\Teorion\Pipeline;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Teoprayoga\Teorion\Exceptions\DisallowedScopeException;
use Teoprayoga\Teorion\Exceptions\ScopeMethodNotFoundException;

final class ScopeResolver
{
    public function resolve(Builder $query, array $scopeEntries, array $allowedScopes, Request $request): Builder
    {
        foreach ($scopeEntries as $entry) {
            [$scopeName, $params, $isLegacy] = $this->parseEntry($entry);

            if (!$scopeName) {
                continue;
            }

            // Whitelist check — always enforced regardless of strict_mode
            if (!in_array($scopeName, $allowedScopes, true)) {
                throw new DisallowedScopeException($scopeName);
            }

            // Method existence check — programmatic safety
            $methodName = 'scope' . ucfirst($scopeName);
            if (!method_exists($query->getModel(), $methodName)) {
                if (config('teorion.strict_mode', false)) {
                    throw new ScopeMethodNotFoundException($scopeName, get_class($query->getModel()));
                }
                continue;
            }

            // Legacy format: pass full $request (backward compat)
            // New format: pass ScopedRequest with only the declared params
            $scopeRequest = $isLegacy
                ? $request
                : ScopedRequest::from($request, $params);

            $query = $query->$scopeName($scopeRequest);
        }

        return $query;
    }

    /**
     * Parse a scopes[] entry into [scopeName, params, isLegacy].
     *
     * Legacy format: "scopeName" (plain string)
     *   → passes full $request, no param isolation
     *
     * New format: ['name' => 'scopeName', 'params' => ['key' => 'value']]
     *   → passes ScopedRequest with isolated params
     */
    private function parseEntry(mixed $entry): array
    {
        if (is_string($entry)) {
            return [$entry, [], true];
        }

        if (is_array($entry) && isset($entry['name'])) {
            return [$entry['name'], $entry['params'] ?? [], false];
        }

        return [null, [], false];
    }
}
