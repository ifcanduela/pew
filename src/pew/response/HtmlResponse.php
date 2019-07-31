<?php

namespace pew\response;

use pew\View;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * This class encapsulates the template rendering functionality.
 */
class HtmlResponse extends Response
{
    /** @var Response */
    protected $response;

    /** @var \pew\View */
    protected $view;

    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     *
     * @param string $templatesFolder
     * @param SymfonyResponse $response
     */
    public function __construct(View $view, SymfonyResponse $response = null, SymfonySession $session = null)
    {
        parent::__construct($response, $session);

        $this->view = $view;
    }

    /**
     * Do not wrap the template in a layout.
     *
     * @return self
     */
    public function noLayout()
    {
        $this->view->layout(false);

        return $this;
    }

    /**
     * Set the view title.
     *
     * @param string $title The title of the view
     * @return self
     */
    public function title($title)
    {
        $this->view->title($title);

        return $this;
    }

    /**
     * Adds content to the response
     * @return Response
     */
    protected function prepareResponse(): SymfonyResponse
    {
        if ($this->isJsonResponse) {
            $this->response->setContent(json_encode($this->variables));
            $this->response->headers->set("Content-Type", "application/json");
        } else {
            $this->response->setContent($this->view->render());
        }

        return $this->response;
    }
}
