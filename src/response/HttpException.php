<?php

declare(strict_types=1);

namespace pew\response;

use Exception;
use ReturnTypeWillChange;

class HttpException extends Exception
{
    #[ReturnTypeWillChange]
    public function __toString()
    {
        return "HTTP $this->code/$this->message";
    }
}
