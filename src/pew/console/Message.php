<?php

namespace pew\console;

class Message
{
    const COLOR_BLACK = 'black';
    const COLOR_RED = 'red';
    const COLOR_GREEN = 'green';
    const COLOR_YELLOW = 'yellow';
    const COLOR_BLUE = 'blue';
    const COLOR_MAGENTA = 'magenta';
    const COLOR_CYAN = 'cyan';
    const COLOR_WHITE = 'white';
    const COLOR_DEFAULT = 'default';

    /** @var string Text to format and print */
    protected $text = '';

    /** @var int|null Width of the text, use NULL for automatic width */
    public $width = null;

    /** @var array Codes to apply the formatting */
    public $setCodes = [];

    /** @var array Codes to reverse the formatting */
    public $unsetCodes = [];

    /** @var boolean Add a line break to the end of message */
    public $newLine = true;

    /** @var array Map of color names to color codes */
    public $colors = [
        self::COLOR_BLACK => 0,
        self::COLOR_RED => 1,
        self::COLOR_GREEN => 2,
        self::COLOR_YELLOW => 3,
        self::COLOR_BLUE => 4,
        self::COLOR_MAGENTA => 5,
        self::COLOR_CYAN => 6,
        self::COLOR_WHITE => 7,
        self::COLOR_DEFAULT => 9,
    ];

    /**
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = $text;
    }

    /**
     * Set the foreground color.
     *
     * @param string $color
     * @return self
     */
    public function fg(string $color)
    {
        $this->setCodes[] = 30 + $this->colors[$color];
        $this->unsetCodes[] = 39;

        return $this;
    }

    /**
     * Set the background color.
     *
     * @param string $color
     * @return self
     */
    public function bg(string $color)
    {
        $this->setCodes[] = 40 + $this->colors[$color];
        $this->unsetCodes[] = 49;

        return $this;
    }

    /**
     * Append a line break to the message.
     *
     * @param bool $eol
     * @return Message
     */
    public function eol($eol = true)
    {
        $this->newLine = $eol;

        return $this;
    }

    /**
     * Do not append a line break to the message.
     *
     * @param bool $inline
     * @return Message
     */
    public function inline($inline = true)
    {
        $this->newLine = ! $inline;

        return $this;
    }

    /**
     * Toggle the 'bold' format flag.
     *
     * @return self
     */
    public function bold()
    {
        $this->setCodes[] = 1;
        $this->unsetCodes[] = 22;

        return $this;
    }

    /**
     * Toggle the 'dim' format flag.
     *
     * @return self
     */
    public function dim()
    {
        $this->setCodes[] = 2;
        $this->unsetCodes[] = 22;

        return $this;
    }

    /**
     * Toggle the 'underline' format flag.
     *
     * @return self
     */
    public function underline()
    {
        $this->setCodes[] = 4;
        $this->unsetCodes[] = 24;

        return $this;
    }

    /**
     * Toggle the 'blink' format flag.
     *
     * @return self
     */
    public function blink()
    {
        $this->setCodes[] = 5;
        $this->unsetCodes[] = 25;

        return $this;
    }

    /**
     * Toggle the 'invert' format flag.
     *
     * @return self
     */
    public function invert()
    {
        $this->setCodes[] = 7;
        $this->unsetCodes[] = 27;

        return $this;
    }

    /**
     * Toggle the 'hidden' format flag.
     *
     * @return self
     */
    public function hidden()
    {
        $this->setCodes[] = 8;
        $this->unsetCodes[] = 28;

        return $this;
    }

    /**
     * Set the width of the text.
     *
     * @param int $width
     * @return self
     */
    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Format a line of text.
     *
     * @param string $text
     * @return string
     */
    public function format(string $text = null): string
    {
        $set = join(';', $this->setCodes);
        $unset = join(';', $this->unsetCodes);

        $text = $text ?? $this->text;
        $width = $this->width ?? strlen($text);
        $eol = $this->newLine ? PHP_EOL : '';

        $text = str_pad($text, $width, ' ', STR_PAD_RIGHT);

        return "\033[{$set}m{$text}\033[{$unset}m{$eol}";
    }

    public function __toString()
    {
        return $this->format();
    }

    public function print()
    {
        echo $this->format();
    }

    public function printLine()
    {
        echo $this->format() . PHP_EOL;
    }
}
