<?php

namespace pew\response;

use pew\lib\Session;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class RedirectResponse extends Response
{
    /** @var string */
    protected $uri;

    public function __construct(string $uri, SymfonyResponse $response = null, Session $session = null)
    {
        if (!$response) {
            $response = new SymfonyRedirectResponse($uri);
        }

        parent::__construct($response, $session);
        $this->uri = $uri;
    }

    public function getResponse(): SymfonyResponse
    {
        $this->response->setTargetUrl($this->uri);

        return $this->response;
    }
}
