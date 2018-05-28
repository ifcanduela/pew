<?php
namespace pew\request;

use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class Middleware
{
    /**
     * @param $uri
     * @return RedirectResponse
     */
    public function redirect($uri)
    {
        return new RedirectResponse($uri);
    }
}
