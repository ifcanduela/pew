<?php

declare(strict_types=1);

namespace pew\request;

use ifcanduela\router\Route;
use RuntimeException;

use function pew\str;

class ActionResolver
{
    public const NAMESPACE_SEPARATOR = "\\";

    protected Route $route;

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
     * @return string|callable
     */
    public function getController(string $controllerNamespace = "\\app\\controllers\\")
    {
        $controllerNamespace = (string) str($controllerNamespace)
            ->ensureStart(static::NAMESPACE_SEPARATOR)
            ->ensureEnd(static::NAMESPACE_SEPARATOR);

        // The handler can be a string like "controller@action" or a callback function
        $handler = $this->route->getHandler();

        if (is_string($handler)) {
            $controllerClassName = $this->getControllerClassName($handler);

            // The namespace is the default controller namespace with an optional,
            // additional namespace set in the route
            $ns = implode(static::NAMESPACE_SEPARATOR, [
                trim($controllerNamespace, static::NAMESPACE_SEPARATOR),
                trim($this->route->getNamespace(), static::NAMESPACE_SEPARATOR),
            ]);
            $namespace = str($ns)
                ->ensureStart(static::NAMESPACE_SEPARATOR)
                ->ensureEnd(static::NAMESPACE_SEPARATOR);

            // Check if the controller class exists -- it may have an optional "Controller" suffix
            foreach ([$controllerClassName, $controllerClassName . "Controller"] as $c) {
                if (class_exists($namespace . $c)) {
                    // Return the FQCN of the controller
                    return $namespace . $c;
                }
            }

            throw new RuntimeException("No controller found for handler `{$handler}`");
        }

        return $handler;
    }

    /**
     * Extract the controller class name from a route handler definition.
     *
     * Route handlers can be defined as `namespace\controller@action`
     *
     * @param string $handler
     * @return string
     */
    public function getControllerClassName(string $handler): string
    {
        // Separate controller and action
        $handlerParts = explode("@", $handler);
        // Separate controller class and namespaces
        $controllerParts = preg_split("~[\\\/]~", $handlerParts[0]);
        // Get controller slug
        $controllerSlug = array_pop($controllerParts);
        // Turn the controller slug into a class name
        $controllerParts[] = (string) str($controllerSlug)->camel()->title();

        // Assemble the controller identifier
        return implode("\\", $controllerParts);
    }

    /**
     * Get the action method name.
     *
     * @param string $defaultAction
     *
     * @return ?string
     */
    public function getAction(string $defaultAction = "index"): ?string
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

        return (string) str($actionSlug)->camel();
    }
}
