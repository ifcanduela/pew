<?php declare(strict_types=1);

namespace pew\response;

use pew\lib\Session;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class RedirectResponse extends Response
{
    /** @var string */
    protected string $uri;

    /**
     * Creates a RedirectResponse wrapper.
     *
     * @param string $uri
     * @param ?SymfonyResponse $response
     * @param ?Session $session
     */
    public function __construct(string $uri, SymfonyResponse $response = null, Session $session = null)
    {
        if (!$response) {
            $response = new SymfonyRedirectResponse($uri);
        }

        parent::__construct($response, $session);
        $this->uri = $uri;
    }

    /**
     * Retrieve the response object.
     *
     * @return SymfonyResponse
     */
    public function getResponse(): SymfonyResponse
    {
        $this->response->setTargetUrl($this->uri);

        return $this->response;
    }
}
