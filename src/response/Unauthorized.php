<?php declare(strict_types=1);

namespace pew\response;

class Unauthorized extends HttpException
{
    protected int $code = 401;

    protected string $message = "Unauthorized";
}
