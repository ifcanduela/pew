<?php

namespace pew\request;

use pew\router\Route;
use Stringy\Stringy as S;

class ActionResolver
{
    protected $route;

    /**
     * Create an ActionResolver.
     *
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * Get the controller class name.
     *
     * @param string $controllerNamespace
     * @return string
     */
    public function getController(string $controllerNamespace = "\\app\\controllers\\")
    {
        # The handler can be a string like "controller@action" or a callback function
        $handler = $this->route->getHandler();
        $controllerNamespace = (string) S::create($controllerNamespace)
            ->ensureLeft("\\")
            ->ensureRight("\\");

        if (is_string($handler)) {
            # Separate controller and action
            $handlerParts = explode("@", $handler);
            # Separate controller class and namespaces
            $controllerParts = preg_split("~[\\\/]~", $handlerParts[0]);
            # Get controller slug
            $controllerSlug = array_pop($controllerParts);
            # Turn the controller slug into a class name
            $controllerParts[] = S::create($controllerSlug)->upperCamelize();
            # Assemble the controller identifier
            $controllerId = join("\\", $controllerParts);
            # The namespace is the default controller namespace with an optional,
            # additional namespace set in the route
            $ns = implode("\\", [
                    trim($controllerNamespace, "\\"),
                    trim($this->route->getNamespace(), "\\")
                ]);
            $namespace = S::create($ns)->ensureLeft("\\")->ensureRight("\\");

            # Check if the controller class exists -- it may have an optional "Controller" suffix
            foreach ([$controllerId, $controllerId . "Controller"] as $c) {
                if (class_exists($namespace . $c)) {
                    # Return the FQCN of the controller
                    return $namespace . $c;
                }
            }

            throw new \RuntimeException("No controller found for handler `{$handler}`");
        }

        return $handler;
    }

    /**
     * Get the action method name.
     *
     * @param string $defaultAction
     * @return string
     */
    public function getAction(string $defaultAction = "index")
    {
        $handler = $this->route->getHandler();

        if (is_callable($handler)) {
            return null;
        }

        if (is_string($handler)) {
            $parts = explode("@", $handler);

            if (isset($parts[1])) {
                return $parts[1];
            }
        }

        $actionSlug = $this->route->getParam("action", $defaultAction);

        return S::create($actionSlug)->camelize();
    }
}
