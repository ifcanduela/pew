<?php

declare(strict_types=1);

namespace pew\response;

class NotFound extends HttpException
{
    /** @var int */
    protected $code = 404;

    /** @var string */
    protected $message = "Not Found";
}
