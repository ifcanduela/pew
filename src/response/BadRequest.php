<?php declare(strict_types=1);

namespace pew\response;

class BadRequest extends HttpException
{
    protected int $code = 400;

    protected string $message = "Bad Request";
}
