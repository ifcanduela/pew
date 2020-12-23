<?php declare(strict_types=1);

namespace pew\commands;


use Exception;
use ifcanduela\router\Group;
use ifcanduela\router\Route;
use ifcanduela\router\Router;
use pew\console\Command;
use pew\console\CommandArguments;

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
     * @param Router $router
     * @return void
     * @throws Exception
     */
    public function run(CommandArguments $arguments, Router $router)
    {
        $list = [
            ["Methods", "Path", "Handler", "Name"],
        ];

        foreach ($router->getRoutes() as $route) {
            $list[] = $this->processRoute($route);
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
     * @param Route $route
     * @return array
     */
    public function processRoute(Route $route): array
    {
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
}
