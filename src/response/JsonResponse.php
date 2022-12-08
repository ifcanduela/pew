<?php

declare(strict_types=1);

namespace pew\response;

use pew\lib\Session;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;

class JsonResponse extends Response
{
    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     *
     * @param mixed $data
     * @param SymfonyJsonResponse|null $response
     * @param Session|null $session
     */
    public function __construct($data = null, SymfonyJsonResponse $response = null, Session $session = null)
    {
        $response ??= new SymfonyJsonResponse();
        $response->setData($data);

        parent::__construct($response, $session);
    }
}
