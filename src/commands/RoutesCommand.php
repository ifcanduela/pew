<?php

namespace pew\commands;

use pew\console\Command;
use pew\console\CommandArguments;

use pew\router\Group;
use pew\router\Route;

class RoutesCommand extends Command
{
    /** @var string */
    public $name = "routes";

    /** @var string */
    public $description = "List application routes.";

    /**
     * Run the command.
     *
     * @param CommandArguments $arguments
     * @return void
     */
    public function run(CommandArguments $arguments, $routes)
    {
        $list = [
            ["Methods", "Path", "Handler", "Name"],
        ];

        foreach ($routes as $route) {
            if ($route instanceof Group) {
                foreach ($route->getRoutes() as $r) {
                    $list[] = $this->processRoute($r, $route);
                }
            } else {
                $list[] = $this->processRoute($route);
            }
        }

        $longest = [0, 0, 0, 0];

        foreach ($list as $r) {
            foreach ($r as $i => $v) {
                $len = mb_strlen($v);

                if ($len > $longest[$i]) {
                    $longest[$i] = $len;
                }
            }
        }

        $headers = array_shift($list);

        $sep = str_repeat("-", $longest[0]) . "  "
            . str_repeat("-", $longest[1]) . "  "
            . str_repeat("-", $longest[2]) . "  "
            . str_repeat("-", $longest[3]);
        $head = str_pad($headers[0], $longest[0], " ", STR_PAD_RIGHT) . "  "
            . str_pad($headers[1], $longest[1], " ", STR_PAD_RIGHT) . "  "
            . str_pad($headers[2], $longest[2], " ", STR_PAD_RIGHT) . "  "
            . $headers[3];

        $this->message($sep);
        $this->message($head);
        $this->message($sep);
        foreach ($list as $r) {
            $name    = "<info>"      . str_pad($r[0], $longest[0], " ", STR_PAD_RIGHT) . "</>";
            $method  = "<comment>"   . str_pad($r[1], $longest[1], " ", STR_PAD_RIGHT) . "</>";
            $handler = "<success>"   . str_pad($r[2], $longest[2], " ", STR_PAD_RIGHT) . "</>";
            $path    = "<info><options=bold>"      . $r[3] . "</></>";
            $this->message($name . "  " . $method . "  " . $handler . "  " . $path);
        }
        $this->message($sep);
    }

    /**
     * Extract information from a route object or array.
     *
     * @param Route|array $route
     * @return array
     */
    public function processRoute($route)
    {
        if ($route instanceof \pew\Router\Route) {
            $name = $route->getName();
            $handler = $route->getHandler();
            $methods = $route->getMethods();

            return [
                implode(", ", $methods),
                $route->getPath(),
                is_string($handler) ? $handler : "<callback>",
                $name ?: "-",
            ];
        }

        return ["-", "GET, POST", $route["handler"], $route["path"]];
    }
}
