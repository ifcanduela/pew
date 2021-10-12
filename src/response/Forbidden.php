<?php declare(strict_types=1);

namespace pew\response;

class Forbidden extends HttpException
{
    /** @var int */
    protected $code = 403;

    /** @var string */
    protected $message = "Forbidden";
}
