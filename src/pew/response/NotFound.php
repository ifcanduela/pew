<?php

namespace pew\response;

class NotFound extends HttpException
{
    protected $code = 404;
    protected $message = "Not Found";
}
