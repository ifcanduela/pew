<?php

namespace pew\response;

class InternalServerError extends HttpException
{
    protected $code = 500;
    protected $message = "Internal Server Error";
}
