<?php declare(strict_types=1);

namespace pew\response;

class Forbidden extends HttpException
{
    protected $code = 403;
    protected $message = "Forbidden";
}
