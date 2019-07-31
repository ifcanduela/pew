<?php

namespace pew\response;

class Unauthorized extends HttpException
{
    protected $code = 401;
    protected $message = "Unauthorized";
}
