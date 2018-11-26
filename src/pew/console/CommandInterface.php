<?php

namespace pew\console;

interface CommandInterface
{
    /**
     * @return string
     */
    public function name();

    /**
     * @return string
     */
    public function description();

    /**
     * @return void
     */
    public function init();

    /**
     * @return void
     */
    public function finish();
}
