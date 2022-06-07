<?php

declare(strict_types=1);

namespace pew\request;

use pew\response\RedirectResponse;

/**
 * Base, optional definition for a middleware service.
 */
abstract class Middleware
{
    /**
     * Redirect to a URL.
     *
     * @param string $uri
     * @return RedirectResponse
     */
    final public function redirect(string $uri)
    {
        return new RedirectResponse($uri);
    }
}
