<?php

namespace pew\console;

interface CommandInterface
{
    public function name();
    public function description();
    public function init();
    public function finish();
}
