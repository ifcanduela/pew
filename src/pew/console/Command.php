<?php

namespace pew\console;

/**
 * Base class for command-line scripts.
 *
 * Commands must implement the name(), description() and run() methods
 * from CommandInterface.
 *
 * The init() and finish() methods are called by the command app
 * before and after, respectively, calling the run() method.
 */
abstract class Command implements CommandInterface
{
    /**
     * Setup the command before running.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Clean up after the command runs.
     *
     * @return void
     */
    public function finish()
    {
    }

    /**
     * Specify default values for command-line arguments.
     *
     * @return array
     */
    public function getDefaultArguments()
    {
        return [];
    }

    /**
     * Create a command-line message to be printed.
     *
     * @param string $text
     * @return Message
     */
    public function message(string $text)
    {
        return new Message($text);
    }

    /**
     * Create an info message with light blue text.
     *
     * @param string $text
     * @return Message
     */
    public function info(string $text)
    {
        return $this->message($text)->fg("cyan");
    }

    /**
     * Create an success message with green text.
     *
     * @param string $text
     * @return Message
     */
    public function success(string $text)
    {
        return $this->message($text)->fg("green");
    }

    /**
     * Create a warning message with yellow text.
     *
     * @param string $text
     * @return Message
     */
    public function warning(string $text)
    {
        return $this->message($text)->fg("yellow");
    }

    /**
     * Create a warning message with black text on red background.
     *
     * @param string $text
     * @return Message
     */
    public function error(string $text)
    {
        return $this->message($text)->fg("black")->bg("red");
    }

    /**
     * Create a block of text to be printed.
     *
     * @param string|string[] ...$text
     * @return MessageBox
     */
    public function messageBox(string ...$text)
    {
        return new MessageBox(...$text);
    }

    /**
     * Create an info message box with light blue background and white text.
     *
     * @param string|string[] ...$text
     * @return Message
     */
    public function infoBox(string ...$text)
    {
        return $this->messageBox(...$text)->fg("white")->bg("cyan");
    }

    /**
     * Create an info message box with green background and white text.
     *
     * @param string|string[] ...$text
     * @return Message
     */
    public function successBox(string ...$text)
    {
        return $this->messageBox(...$text)->fg("white")->bg("green");
    }

    /**
     * Create a warning message box with yellow background and black text.
     *
     * @param string|string[] ...$text
     * @return Message
     */
    public function warningBox(string ...$text)
    {
        return $this->messageBox(...$text)->fg("black")->bg("yellow");
    }

    /**
     * Create an error message box with red background and black text.
     *
     * @param string|string[] ...$text
     * @return Message
     */
    public function errorBox(string ...$text)
    {
        return $this->messageBox(...$text)->fg("black")->bg("red");
    }
}
