<?php

namespace pew\response;

use pew\lib\Session;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;

class JsonResponse extends Response
{
    /**
     * @var mixed
     */
    protected $data;

    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     *
     * @param mixed $data
     * @param SymfonyResponse $response
     * @param Session $session
     */
    public function __construct($data, $response = null, Session $session = null)
    {
        if ($response instanceof \pew\response\Response) {
            $response = $response->response;
        }

        if (!$response) {
            $response = new SymfonyJsonResponse();
        }

        parent::__construct($response, $session);

        $this->data = $data;
    }

    /**
     * Set the JSON data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the JSON data.
     *
     * @param array $data
     * @return self
     */
    public function setData(array $data)
    {
        $this->response->setData($data);

        return $this;
    }

    /**
     * Prepare the response.
     *
     * @return SymfonyJsonResponse
     */
    public function getResponse(): SymfonyResponse
    {
        $this->response->setData($this->data);

        return $this->response;
    }
}
