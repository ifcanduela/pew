<?php

namespace pew\response;

use pew\lib\Session;
use pew\response\Response;
use pew\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * This class encapsulates the template rendering functionality.
 */
class HtmlResponse extends Response
{
    /** @var View */
    protected $view;

    /**
     * Creates a View object based on a folder.
     *
     * If no folder is provided, the current working directory is used.
     *
     * @param View $view
     * @param SymfonyResponse $response
     * @param Session $session
     */
    public function __construct(View $view, SymfonyResponse $response = null, Session $session = null)
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
     * Get the response object.
     *
     * @return SymfonyResponse
     */
    public function getResponse(): SymfonyResponse
    {
        if ($this->isJsonResponse) {
            $this->response->setContent(json_encode($this->variables));
            $this->response->headers->set("Content-Type", "application/json");
        } elseif ($this->view) {
            $this->response->setContent($this->view->render());
        }

        return $this->response;
    }
}
