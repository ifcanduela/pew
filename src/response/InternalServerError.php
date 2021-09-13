<?php declare(strict_types=1);

namespace pew\response;

class InternalServerError extends HttpException
{
    protected int $code = 500;

    protected string $message = "Internal Server Error";
}
