<?php

namespace pew\response;

class Forbidden extends HttpException
{
    protected $code = 403;
    protected $message = "Forbidden";
}
