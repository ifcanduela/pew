<?php declare(strict_types=1);

namespace pew\response;

class Unauthorized extends HttpException
{
    protected $code = 401;
    protected $message = "Unauthorized";
}
