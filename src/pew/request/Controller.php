<?php

namespace pew\request;

use pew\request\Request;
use pew\libs\Session;
use pew\View;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The basic controller class, with some common methods and fields.
 */
class Controller
{
    /**
     * @var \pew\request\Request
     */
    public $request;

    /**
     * @var \pew\View
     */
    public $view;

    public function __construct(Request $request, View $view = null)
    {
        $this->request = $request;
        $this->view = $view;
    }

    /**
     * Redirect to another app path.
     *
     * @param string $uri
     * @return RedirectResponse
     */
    public function redirect(string $uri)
    {
        return new RedirectResponse($uri);
    }

    /**
     * Render a template.
     *
     * The $template argument can be skipped with `null` if a call
     * to `$view->template(string $template)` has been made beforehand.
     *
     * @param string $template
     * @param array $data
     * @return Response
     */
    public function render(string $template, array $data = [])
    {
        if ($template !== null) {
            $this->view->template($template);
        }

        $content = $this->view->render($template, $data);

        return new Response($content);
    }

    /**
     * Render a JSON response
     *
     * @param mixed $data
     * @return JsonResponse
     */
    public function renderJson($data)
    {
        return new JsonResponse($data);
    }
}
