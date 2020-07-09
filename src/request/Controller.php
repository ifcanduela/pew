<?php declare(strict_types=1);

namespace pew\request;

use Exception;
use pew\response\HtmlResponse;
use pew\response\JsonResponse;
use pew\response\RedirectResponse;
use pew\View;

/**
 * The basic controller class, with some common methods and fields.
 */
class Controller
{
    /** @var Request */
    public $request;

    /** @var View */
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
     * @return RedirectResponse
     */
    public function redirect(string $uri)
    {
        return new RedirectResponse($uri);
    }

    /**
     * Render a JSON response
     *
     * @param mixed $data
     * @return JsonResponse
     */
    public function json($data)
    {
        return new JsonResponse($data);
    }

    /**
     * Render a template.
     *
     * The $template argument can be skipped with `null` if a call
     * to `$view->template(string $template)` has been made beforehand.
     *
     * @param string $template
     * @param array  $data
     * @return HtmlResponse
     * @throws Exception
     */
    public function render(string $template, array $data = [])
    {
        if ($template !== null) {
            $this->view->template($template);
        }

        $this->view->setData($data);

        return new HtmlResponse($this->view);
    }
}
