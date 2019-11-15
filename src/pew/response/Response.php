<?php

namespace pew\response;

use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

/**
 * This class represents a response.
 */
class Response
{
    /** @var bool */
    protected $isJsonResponse = false;

    /** @var Response */
    protected $response;

    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     *
     * @param SymfonyResponse $response
     * @param SymfonySession $session
     */
    public function __construct(SymfonyResponse $response = null, SymfonySession $session = null)
    {
        $this->response = $response ?? new SymfonyResponse();
        $this->session = $session ?? new SymfonySession();
    }

    /**
     * Set the HTTP response status code.
     *
     * @param int $httpStatusCode
     * @return self
     */
    public function code(int $httpStatusCode)
    {
        $this->response->setStatusCode($httpStatusCode);

        return $this;
    }

    /**
     * Set a cookie on the response.
     *
     * @param SymfonyCookie|string $cookie
     * @param string|null $value
     * @return self
     */
    public function cookie($cookie, string $value = null)
    {
        if ($cookie instanceof SymfonyCookie) {
            $this->response->headers->setCookie($cookie);
        } else {
            $this->response->headers->setCookie(new SymfonyCookie($cookie, $value));
        }

        return $this;
    }

    /**
     * Set a flash message.
     *
     * @param string $type
     * @param string $message
     * @return self
     */
    public function flash(string $type, string $message)
    {
        $this->session->getFlashBag()->add($type, $message);

        return $this;
    }

    /**
     * Set a header on the response.
     *
     * @param string $header
     * @param string $value
     * @return self
     */
    public function header(string $header, string $value)
    {
        $this->response->headers->set($header, $value);

        return $this;
    }

    /**
     * Return a JSON response instead of rendering a template.
     *
     * @param bool $isJsonResponse
     * @return self
     */
    public function json($isJsonResponse = true)
    {
        $this->isJsonResponse = true;

        return $this;
    }

    /**
     * Preprocess the response.
     *
     * @return Response
     */
    protected function prepareResponse(): SymfonyResponse
    {
        return $this->response;
    }

    /**
     * Convert the response to string.
     *
     * @return string
     */
    public function __toString()
    {
        $response = $this->prepareResponse();

        return (string) $response;
    }

    /**
     * Send the response.
     *
     * @return SymfonyResponse
     */
    public function send(): SymfonyResponse
    {
        $response = $this->prepareResponse();

        return $response->send();
    }
}
