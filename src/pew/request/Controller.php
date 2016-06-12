<?php

namespace pew\request;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The basic controller class, with some common methods and fields.
 *
 * @package pew
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Controller
{
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
     * @param array $data
     * @return Response
     */
    public function render(array $data = []): Response
    {
        return new Response($this->view->render($data));
    }

    /**
     * Render a JSON response
     * 
     * @param array $data
     * @return JsonResponse
     */
    public function renderJson(array $data): JsonResponse
    {
        return new JsonResponse($data);
    }
}
