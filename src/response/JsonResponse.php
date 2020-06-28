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
    public function __construct($data, SymfonyResponse $response = null, Session $session = null)
    {
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
        if ($this->response instanceof SymfonyJsonResponse) {
            $this->response->setData($data);
        } else {
            $this->response->setContent(json_encode($data));
        }

        return $this;
    }

    /**
     * Get the response object.
     *
     * @return SymfonyResponse
     */
    public function getResponse(): SymfonyResponse
    {
        $this->setData($this->data);
        $this->response->headers->set("Content-Type", "application/json");

        return $this->response;
    }
}
