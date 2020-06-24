<?php

namespace pew\response;

class NotImplemented extends HttpException
{
    protected $code = 501;
    protected $message = "Not Implemented";
}
