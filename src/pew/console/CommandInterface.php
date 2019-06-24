<?php

namespace pew\console;

interface CommandInterface
{
    /**
     * @return void
     */
    public function init();

    /**
     * @return void
     */
    public function finish();

    /**
     * @return array
     */
    public function getDefaultArguments();
}
