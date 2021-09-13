<?php declare(strict_types=1);

namespace pew\response;

use pew\lib\Session as Session;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * This class represents a response.
 */
class Response
{
    protected bool $isJsonResponse = false;

    protected SymfonyResponse $response;

    protected string $content = "";

    protected Session $session;

    /**
     * Creates a generic Response wrapper.
     *
     * @param ?SymfonyResponse $response
     * @param ?Session $session
     */
    public function __construct(SymfonyResponse $response = null, Session $session = null)
    {
        $this->response = $response ?? new SymfonyResponse();
        $this->session = $session ?? new Session();
    }

    /**
     * Set the HTTP response status code.
     *
     * @param int $httpStatusCode
     * @return self
     */
    public function code(int $httpStatusCode): Response
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
    public function cookie($cookie, string $value = null): Response
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
    public function flash(string $type, string $message): Response
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
    public function header(string $header, string $value): Response
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
    public function json($isJsonResponse = true): Response
    {
        $this->isJsonResponse = $isJsonResponse;

        return $this;
    }

    /**
     * Set the text content of the response.
     *
     * @param string $content
     * @return self
     */
    public function setContent(string $content): Response
    {
        $this->response->setContent($content);

        return $this;
    }

    /**
     * Get the text content of the response.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->response->getContent();
    }

    /**
     * Preprocess the response.
     *
     * @return SymfonyResponse
     */
    public function getResponse(): SymfonyResponse
    {
        return $this->response;
    }

    /**
     * Convert the response to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        $response = $this->getResponse();

        return (string) $response;
    }

    /**
     * Send the response.
     *
     * @return SymfonyResponse
     */
    public function send(): SymfonyResponse
    {
        $response = $this->getResponse();

        return $response->send();
    }
}
