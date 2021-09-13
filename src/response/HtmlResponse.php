<?php declare(strict_types=1);

namespace pew\response;

use Exception;
use pew\lib\Session;
use pew\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * This class encapsulates the template rendering functionality.
 */
class HtmlResponse extends Response
{
    protected View $view;

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
     * @throws Exception
     */
    public function getResponse(): SymfonyResponse
    {
        $this->response->setContent($this->view->render());

        return $this->response;
    }
}
