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
 *
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Controller
{
    /**
     * @var pew\request\Request
     */
    public $request;

    /**
     * @var pew\libs\Session
     */
    public $session;

    /**
     * @var pew\View
     */
    public $view;

    public function __construct(Request $request, Session $session, View $view)
    {
        $this->request = $request;
        $this->session = $session;
        $this->view = $view;
    }

    /**
     * Redirect to another app path.
     *
     * @param string $uri
     * @return RedirectResponse
     */
    public function redirect($uri): RedirectResponse
    {
        return new RedirectResponse($uri);
    }

    /**
     * Render a template.
     *
     * The $template argument can be skipped
     *
     * @param string $template
     * @param array $data
     * @return Response
     */
    public function render($template, $data = []): Response
    {
        if (is_string($template)) {
            $this->view->template($template);
        } else {
            $data = $template;
        }

        return new Response($this->view->render($data));
    }

    /**
     * Render a JSON response
     *
     * @param mixed $data
     * @return JsonResponse
     */
    public function renderJson($data): JsonResponse
    {
        return new JsonResponse($data);
    }
}
