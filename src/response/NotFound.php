<?php declare(strict_types=1);

namespace pew\response;

class NotFound extends HttpException
{
    protected int $code = 404;

    protected string $message = "Not Found";
}
