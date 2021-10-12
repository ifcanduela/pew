<?php declare(strict_types=1);

namespace pew\response;

class BadRequest extends HttpException
{
    /** @var int */
    protected $code = 400;

    /** @var string */
    protected $message = "Bad Request";
}
