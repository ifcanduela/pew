<?php declare(strict_types=1);

namespace pew\response;

class NotImplemented extends HttpException
{
    protected int $code = 501;

    protected string $message = "Not Implemented";
}
