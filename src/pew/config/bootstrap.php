<?php

require __DIR__ . '/functions.php';

function exception_error_handler($errno, $errstr, $errfile, $errline )
{
    if (!(error_reporting() & $errno)) {
        return;
    }
    
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

set_error_handler("exception_error_handler");
