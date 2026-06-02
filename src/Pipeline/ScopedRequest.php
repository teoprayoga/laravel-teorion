<?php

namespace Teoprayoga\Teorion\Pipeline;

use Illuminate\Http\Request;

/**
 * Isolated Request that carries only a scope's declared params.
 * Auth (user resolver) and route resolver are preserved from the original request
 * so $request->user() and route model binding still work inside scopes.
 */
final class ScopedRequest extends Request
{
    public static function from(Request $original, array $params): self
    {
        $scoped = new self(
            query:      $params,
            request:    [],
            attributes: $original->attributes->all(),
            cookies:    [],
            files:      [],
            server:     $original->server->all(),
            content:    null,
        );

        $scoped->setUserResolver($original->getUserResolver());
        $scoped->setRouteResolver($original->getRouteResolver());

        return $scoped;
    }
}
