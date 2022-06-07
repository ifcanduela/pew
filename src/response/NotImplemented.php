<?php

declare(strict_types=1);

namespace pew\response;

class NotImplemented extends HttpException
{
    /** @var int */
    protected $code = 501;

    /** @var string */
    protected $message = "Not Implemented";
}
