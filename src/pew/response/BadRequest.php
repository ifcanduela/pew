<?php

namespace pew\response;

class BadRequest extends HttpException
{
    protected $code = 400;
    protected $message = "Bad Request";
}
