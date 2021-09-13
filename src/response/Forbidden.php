<?php declare(strict_types=1);

namespace pew\response;

class Forbidden extends HttpException
{
    protected int $code = 403;

    protected string $message = "Forbidden";
}
