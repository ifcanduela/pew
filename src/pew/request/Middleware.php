<?php
namespace pew\request;

use Symfony\Component\HttpFoundation\RedirectResponse;

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
