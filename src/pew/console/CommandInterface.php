<?php

namespace pew\console;

interface CommandInterface
{
    /**
     * Setup the command before running.
     *
     * @return void
     */
    public function init();

    /**
     * Clean up after the command runs.
     *
     * @return void
     */
    public function finish();

    /**
     * Specify default values for command-line arguments.
     *
     * @return array
     */
    public function getDefaultArguments();
}
