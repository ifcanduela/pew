<?php

namespace pew\commands;

use pew\console\Command;
use pew\console\CommandArguments;
use Stringy\Stringy as S;

use pew\router\Group as RouteGroup;

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
            ["Name", "Methods", "Handler", "Path",],
        ];

        foreach ($routes as $route) {
            if ($route instanceof RouteGroup) {
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

    public function processRoute($r, RouteGroup $group = null)
    {
        if ($r instanceof \pew\Router\Route) {
            $name = $r->getName();
            $handler = $r->getHandler();
            $methods = $r->getMethods();

            return [
                $name ?: "-",
                implode(", ", $methods),
                is_string($handler) ? $handler : "<callback>",
                $r->getPath(),
            ];
        }

        return ["-", "GET, POST", $r["handler"], $r["path"]];
    }
}
