<?php
namespace pew\request;

use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class Middleware
{
    /**
     * @param $uri
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect($uri)
    {
        return new RedirectResponse($uri);
    }
}
