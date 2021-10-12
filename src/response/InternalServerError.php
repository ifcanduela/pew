<?php declare(strict_types=1);

namespace pew\response;

class InternalServerError extends HttpException
{
    /** @var int */
    protected $code = 500;

    /** @var string */
    protected $message = "Internal Server Error";
}
