<?php declare(strict_types=1);

namespace pew\response;

class BadRequest extends HttpException
{
    protected $code = 400;
    protected $message = "Bad Request";
}
