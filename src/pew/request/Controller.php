<?php

namespace pew\request;

use pew\View;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use pew\response\RedirectResponse as PewRedirectResponse;
use pew\response\JsonResponse as PewJsonResponse;
use pew\response\HtmlResponse as PewHtmlResponse;

/**
 * The basic controller class, with some common methods and fields.
 */
class Controller
{
    /** @var Request */
    public $request;

    /** @var \pew\View */
    public $view;

    /**
     * @param Request $request
     * @param View|null $view
     */
    public function __construct(Request $request, View $view = null)
    {
        $this->request = $request;
        $this->view = $view;
    }

    /**
     * Redirect to a URL.
     *
     * @param string $uri
     * @return PewRedirectResponse
     */
    public function redirect(string $uri)
    {
        $response = new SymfonyRedirectResponse($uri);

        return new PewRedirectResponse($response);
    }

    /**
     * Render a JSON response
     *
     * @param mixed $data
     * @return PewJsonResponse
     */
    public function json($data)
    {
        $response = new SymfonyJsonResponse($data);

        return new PewJsonResponse($response);
    }

    /**
     * Render a template.
     *
     * The $template argument can be skipped with `null` if a call
     * to `$view->template(string $template)` has been made beforehand.
     *
     * @param string $template
     * @param array  $data
     * @return PewHtmlResponse
     * @throws \Exception
     */
    public function render(string $template, array $data = [])
    {
        if ($template !== null) {
            $this->view->template($template);
        }

        $this->view->setData($data);

        return new PewHtmlResponse($this->view);
    }
}
