<?php

declare(strict_types=1);

namespace pew\console;

use ReflectionClass;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use function pew\slug;

/**
 * Base class for command-line scripts.
 *
 * Commands must implement a method to be called from the console app, the
 * default being `run`.
 *
 * The init() and finish() methods are called by the console app before and
 * after calling the `run` (or other action) method. Values from the container
 * can be injected.
 */
abstract class Command
{
    /** @var string */
    public string $name = "";

    /** @var string */
    public string $description = "";

    /** @var InputInterface */
    public InputInterface $input;

    /** @var OutputInterface */
    public OutputInterface $output;

    /** @var QuestionHelper */
    private QuestionHelper $questionHelper;

    /** @var FormatterHelper */
    private FormatterHelper $formatterHelper;

    /** @var string */
    public string $defaultCommand = "run";

    /**
     * Command constructor.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = new QuestionHelper();
        $this->formatterHelper = new FormatterHelper();

        if (!$this->name) {
            $className = (new ReflectionClass($this))->getShortName();

            $this->name = (string) slug($className)->beforeLast("-command");
        }
    }

    /**
     * Print a command-line message.
     *
     * @param array|string $text
     * @param bool $newLine
     * @param string $format
     * @return void
     */
    final public function message(array|string $text, bool $newLine = true, string $format = ""): void
    {
        if (!is_array($text)) {
            $text = [$text];
        }

        if ($format) {
            $text = array_map(fn ($t) => "$format$t</>", $text);
        }

        $this->output->write($text, $newLine);
    }

    /**
     * Print an info message with light blue text.
     *
     * @param array|string $text
     * @param bool $newLine
     * @return void
     */
    final public function info(array|string $text, bool $newLine = true): void
    {
        $this->message($text, $newLine, "<info>");
    }

    /**
     * Print a success message with green text.
     *
     * @param array|string $text
     * @param bool $newLine
     * @return void
     */
    final public function success(array|string $text, bool $newLine = true): void
    {
        $this->message($text, $newLine, "<success>");
    }

    /**
     * Print a warning message with yellow text.
     *
     * @param array|string $text
     * @param bool $newLine
     * @return void
     */
    final public function warning(array|string $text, bool $newLine = true): void
    {
        $this->message($text, $newLine, "<warn>");
    }

    /**
     * Print an error message with red text.
     *
     * @param array|string $text
     * @param bool $newLine
     * @return void
     */
    final public function error(array|string $text, bool $newLine = true): void
    {
        $this->message($text, $newLine, "<error>");
    }

    /**
     * Print a log-style message.
     *
     * @param string $message
     * @param bool $newLine
     * @return void
     */
    final public function log(string $message, bool $newLine = true): void
    {
        $this->message($message, $newLine, "<comment>");
    }

    /**
     * Ask a question and wait for input.
     *
     * @param string $question
     * @param string $defaultAnswer
     * @return string
     */
    final public function ask(string $question, string $defaultAnswer = ""): string
    {
        $q = new Question($question, $defaultAnswer);

        return $this->questionHelper->ask($this->input, $this->output, $q);
    }

    /**
     * Ask a yes/no question and wait for input.
     *
     * @param string $question
     * @param bool $defaultAnswer
     * @return bool
     */
    final public function confirm(string $question, bool $defaultAnswer = true): bool
    {
        $q = new ConfirmationQuestion($question, $defaultAnswer);

        return $this->questionHelper->ask($this->input, $this->output, $q);
    }

    /**
     * Ask the user to choose from a list and wait for input.
     *
     * @param string $question
     * @param string[] $options
     * @param mixed $defaultAnswer
     * @return mixed
     */
    final public function choose(string $question, array $options, mixed $defaultAnswer = null): mixed
    {
        $q = new ChoiceQuestion($question, $options, $defaultAnswer);

        return $this->questionHelper->ask($this->input, $this->output, $q);
    }
}
