<?php

namespace pew\console;

class Message
{
    const COLOR_BLACK = "black";
    const COLOR_RED = "red";
    const COLOR_GREEN = "green";
    const COLOR_YELLOW = "yellow";
    const COLOR_BLUE = "blue";
    const COLOR_MAGENTA = "magenta";
    const COLOR_CYAN = "cyan";
    const COLOR_WHITE = "white";
    const COLOR_DEFAULT = "default";

    const CODE_FG_SET = 30;
    const CODE_FG_UNSET = 39;

    const CODE_BG_SET = 40;
    const CODE_BG_UNSET = 49;

    const CODE_BOLD_SET = 1;
    const CODE_BOLD_UNSET = 22;

    const CODE_DIM_SET = 2;
    const CODE_DIM_UNSET =  22;

    const CODE_UNDERLINE_SET = 4;
    const CODE_UNDERLINE_UNSET =  24;

    const CODE_BLINK_SET = 5;
    const CODE_BLINK_UNSET =  25;

    const CODE_INVERT_SET = 7;
    const CODE_INVERT_UNSET =  27;

    const CODE_HIDDEN_SET = 8;
    const CODE_HIDDEN_UNSET =  28;

    /** @var string Text to format and print */
    protected $text = "";

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
        $this->setCodes[] = static::CODE_FG_SET + $this->colors[$color];
        $this->unsetCodes[] = static::CODE_FG_UNSET;

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
        $this->setCodes[] = static::CODE_BG_SET + $this->colors[$color];
        $this->unsetCodes[] = static::CODE_BG_UNSET;

        return $this;
    }

    /**
     * Append a line break to the message.
     *
     * @param bool $eol
     * @return self
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
     * @return self
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
        $this->setCodes[] = static::CODE_BOLD_SET;
        $this->unsetCodes[] = static::CODE_BOLD_UNSET;

        return $this;
    }

    /**
     * Toggle the 'dim' format flag.
     *
     * @return self
     */
    public function dim()
    {
        $this->setCodes[] = static::CODE_DIM_SET;
        $this->unsetCodes[] = static::CODE_DIM_UNSET;

        return $this;
    }

    /**
     * Toggle the 'underline' format flag.
     *
     * @return self
     */
    public function underline()
    {
        $this->setCodes[] = static::CODE_UNDERLINE_SET;
        $this->unsetCodes[] = static::CODE_UNDERLINE_UNSET;

        return $this;
    }

    /**
     * Toggle the 'blink' format flag.
     *
     * @return self
     */
    public function blink()
    {
        $this->setCodes[] = static::CODE_BLINK_SET;
        $this->unsetCodes[] = static::CODE_BLINK_UNSET;

        return $this;
    }

    /**
     * Toggle the 'invert' format flag.
     *
     * @return self
     */
    public function invert()
    {
        $this->setCodes[] = static::CODE_INVERT_SET;
        $this->unsetCodes[] = static::CODE_INVERT_UNSET;

        return $this;
    }

    /**
     * Toggle the 'hidden' format flag.
     *
     * @return self
     */
    public function hidden()
    {
        $this->setCodes[] = static::CODE_HIDDEN_SET;
        $this->unsetCodes[] = static::CODE_HIDDEN_UNSET;

        return $this;
    }

    /**
     * Set the width of the text.
     *
     * @param int $width
     * @return self
     */
    public function width(int $width)
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
    public function format(string $text = null)
    {
        $text = $text ?? $this->text;
        $width = $this->width ?? strlen($text);
        $prefix = "";
        $suffix = "";
        $eol = $this->newLine ? PHP_EOL : "";

        $text = str_pad($text, $width, " ", STR_PAD_RIGHT);

        if (count($this->setCodes)) {
            $set = join(";", $this->setCodes);
            $unset = join(";", $this->unsetCodes);

            $prefix = "\033[{$set}m";
            $suffix = "\033[{$unset}m";
        }

        return "{$prefix}{$text}{$suffix}{$eol}";
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
