<?php

namespace pew\libs;

use \pew\libs\Env;
use \pew\libs\Router;
use \pew\libs\Registry;

/**
 * The Request class combines the Env class and Router class.
 * 
 * @package pew/libs
 * @author ifernandez <ifcanduela@gmail.com>
 */
class Request
{
    public $get;
    public $post;
    public $files;
    public $cookie;

    public function __construct(Env $env, Router $route)
    {
        $this->env = $env;
        $this->route = $route;

        $this->get = new Registry($env->get);
        $this->post = new Registry($env->post);
        $this->files = new Registry($env->files);
        $this->cookie = new Registry($env->cookie);

        $this->controller = $route->controller();
        $this->action = $route->action();
        $this->arguments = $route->parameters();
        $this->response_type = $route->response_type();
    }
}
