<?php

declare(strict_types=1);

namespace pew\response;

use Exception;

class HttpException extends Exception
{
    public function __toString()
    {
        return "HTTP {$this->code}/{$this->message}";
    }
}
