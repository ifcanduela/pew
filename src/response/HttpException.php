<?php

namespace pew\response;

class HttpException extends \Exception
{
    public function __toString()
    {
        return "HTTP {$this->code}/{$this->message}";
    }
}
