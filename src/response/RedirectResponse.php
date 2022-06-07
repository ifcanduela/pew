<?php

declare(strict_types=1);

namespace pew\response;

use pew\lib\Session;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class RedirectResponse extends Response
{
    /**
     * Creates a RedirectResponse wrapper.
     *
     * @param string $uri
     * @param ?SymfonyResponse $response
     * @param ?Session $session
     */
    public function __construct(string $uri, SymfonyRedirectResponse $response = null, Session $session = null)
    {
        $response ??= new SymfonyRedirectResponse($uri);
        $response->setTargetUrl($uri);

        parent::__construct($response, $session);
    }
}
