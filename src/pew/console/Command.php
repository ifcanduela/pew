<?php

namespace pew\console;

use Stringy\Stringy;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /**
     * Command constructor.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param FormatterHelper $formatter
     */
    public function __construct(InputInterface $input, OutputInterface $output, FormatterHelper $formatter)
    {
        $this->input = $input;
        $this->output = $output;
        $this->formatter = $formatter;

        if (!$this->name) {
            $className = (new \ReflectionClass($this))->getShortName();

            $this->name = (string) Stringy::create($className)->removeLeft("Command")->slugify();
        }
    }

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
     * Print a command-line message.
     *
     * @param string|array $text
     * @return Message
     */
    public function message($text, bool $newLine = true, string $format = "")
    {
        if (!is_array($text)) {
            $text = [$text];
        }

        if ($format) {
            $text = array_map(function ($t) use ($format) {
                return "{$format}{$t}</>";
            }, $text);
        }

        $this->output->write($text, $newLine);
    }

    /**
     * Print an info message with light blue text.
     *
     * @param string|array $text
     * @return Message
     */
    public function info($text, bool $newLine = true)
    {
        $this->message($text, $newLine, "<fg=cyan>");
    }

    /**
     * Print a success message with green text.
     *
     * @param string|array $text
     * @return Message
     */
    public function success($text, bool $newLine = true)
    {
        $this->message($text, $newLine, "<info>");
    }

    /**
     * Print a warning message with yellow text.
     *
     * @param string|array $text
     * @return Message
     */
    public function warning($text, bool $newLine = true)
    {
        $this->message($text, $newLine, "<comment>");
    }

    /**
     * Print an error message with red text.
     *
     * @param string|array $text
     * @return Message
     */
    public function error($text, bool $newLine = true)
    {
        $this->message($text, $newLine, "<error>");
    }

    /**
     * Print a log-style message.
     *
     * @param string $type
     * @param string $message
     * @return Message
     */
    public function log(string $section, string $message, bool $newLine = true)
    {
        $text = $this->formatter->formatSection($section, $message);
        $this->message($text, $newLine);
    }
}
