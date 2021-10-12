<?php declare(strict_types=1);

namespace pew\response;

class Unauthorized extends HttpException
{
    /** @var int */
    protected $code = 401;

    /** @var string */
    protected $message = "Unauthorized";
}
