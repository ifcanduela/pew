<?php
namespace pew\request;

use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class Middleware
{
    public function redirect($uri): RedirectResponse
    {
        return new RedirectResponse($uri);
    }
}
