<?php declare(strict_types=1);

namespace pew\request;

use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
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
    public function redirect(string $uri)
    {
        return new RedirectResponse($uri);
    }
}
