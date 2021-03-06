<?php declare(strict_types=1);

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
 * Commands has to implement a method which will be called from the console
 * app, the default being `run`.
 *
 * The init() and finish() methods are called by the console app before and
 * after calling the `run` (or other action) method. Values from the container
 * can be injected.
 */
abstract class Command
{
    /** @var string */
    public $name = "";

    /** @var string */
    public $description = "";

    /** @var InputInterface */
    public InputInterface $input;

    /** @var OutputInterface */
    public OutputInterface $output;

    /** @var QuestionHelper */
    private QuestionHelper $questionHelper;

    /** @var FormatterHelper */
    private FormatterHelper $formatterHelper;

    /** @var string */
    public $defaultCommand = "run";

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
     * @param string|array $text
     * @param bool $newLine
     * @param string $format
     * @return void
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
     * @param bool $newLine
     * @return void
     */
    public function info($text, bool $newLine = true)
    {
        $this->message($text, $newLine, "<info>");
    }

    /**
     * Print a success message with green text.
     *
     * @param string|array $text
     * @param bool $newLine
     * @return void
     */
    public function success($text, bool $newLine = true)
    {
        $this->message($text, $newLine, "<success>");
    }

    /**
     * Print a warning message with yellow text.
     *
     * @param string|array $text
     * @param bool $newLine
     * @return void
     */
    public function warning($text, bool $newLine = true)
    {
        $this->message($text, $newLine, "<warn>");
    }

    /**
     * Print an error message with red text.
     *
     * @param string|array $text
     * @param bool $newLine
     * @return void
     */
    public function error($text, bool $newLine = true)
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
    public function log(string $message, bool $newLine = true)
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
    public function ask(string $question, string $defaultAnswer = ""): string
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
    public function confirm(string $question, bool $defaultAnswer = true): bool
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
    public function choose(string $question, array $options, $defaultAnswer = 0)
    {
        $q = new ChoiceQuestion($question, $options, $defaultAnswer);

        return $this->questionHelper->ask($this->input, $this->output, $q);
    }
}
