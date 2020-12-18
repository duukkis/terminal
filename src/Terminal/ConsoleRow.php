<?php

namespace Terminal;

use Terminal\Style\Style;

class ConsoleRow
{
    public string $output;
    private array $styles;

    public function __construct(string $output)
    {
        $this->output = $output;
    }

    public function getOutputTo(int $col): string
    {
        $clearTheseFromStyles = array_fill(0, $col, null);
        $this->styles = array_diff_key($this->styles, $clearTheseFromStyles);
        return substr($this->output, 0, $col);
    }

    public function getOutputFrom(int $col): string
    {
        $clearTheseFromStyles = array_fill($col, strlen($this->output), null);
        $this->styles = array_diff_key($this->styles, $clearTheseFromStyles);
        return substr($this->output, $col);
    }

    public function isEmpty(): bool
    {
        return empty(trim($this->output));
    }

    public function getStyles(): array
    {
        return $this->styles;
    }

    public function addStyles(array $styles)
    {
        $this->styles = $styles;
    }

    public function addStyle(int $col, Style $style)
    {
        $this->styles[$col] = $style;
    }
}