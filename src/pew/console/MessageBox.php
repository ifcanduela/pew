<?php

namespace pew\console;

class MessageBox extends Message
{
    public $width = 80;
    public $margin = 0;
    public $padding = 1;
    public $newLine = true;

    /**
     * MessageBox constructor.
     *
     * @param string|string[] ...$lines
     */
    public function __construct(...$lines)
    {
        parent::__construct($lines);
    }

    /**
     * Set a left margin for the message box.
     *
     * @param int $margin
     * @return self
     */
    public function margin($margin)
    {
        $this->margin = $margin;

        return $this;
    }

    /**
     * Set a padding for the text.
     *
     * @param int $padding
     * @return self
     */
    public function padding(int $padding)
    {
        $this->padding = $padding;

        return $this;
    }

    /**
     * Format a text into several lines.
     *
     * @param string $text
     * @return string
     */
    public function format(string $text = null)
    {
        $text = $text ?? $this->text;
        $newLine = $this->newLine;
        $eol = $this->newLine ? PHP_EOL : "";
        $this->newLine = false;

        if (!is_array($text)) {
            $text = [$text];
        }

        $lines = [];

        foreach ($text as $line) {
            $more = explode("#$#", wordwrap($line, $this->width - ($this->padding * 2), "#$#"));
            $lines = array_merge($lines, $more);
        }

        array_unshift($lines, str_repeat(" ", $this->width - ($this->padding * 2)));
        array_push($lines, str_repeat(" ", $this->width - ($this->padding * 2)));

        $formattedLines = [];

        foreach ($lines as $line) {
            $str = str_pad(
                str_repeat(" ", $this->padding) . $line . str_repeat(" ", $this->padding),
                $this->width,
                " ",
                STR_PAD_RIGHT
            );

            $formattedLines[] = str_repeat(" ", $this->margin) . parent::format($str);
        }

        $this->newLine = $newLine;

        return join(PHP_EOL, $formattedLines) . $eol;
    }
}
