<?php

namespace Terminal;

use Terminal\Style\ClearStyle;
use Terminal\Style\Style;

class ConsoleRow
{
    public string $output;
    private array $styles = [];

    const MIN = 0;
    const MAX = 10000;

    public function __construct(string $output)
    {
        $this->output = $output;
    }

    public function getOutputTo(int $col): string
    {
        return substr($this->output, 0, $col);
    }

    public function getOutputFrom(int $col): string
    {
        return substr($this->output, $col);
    }

    public function isEmpty(): bool
    {
        return empty(trim($this->output));
    }

    public function getStyles(?int $before = self::MAX, ?int $after = self::MIN): array
    {
        return array_filter($this->styles, function($k) use ($before, $after) {
            return ($k >= $after && $k <= $before);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function addStyles(array $styles)
    {
        $this->styles = $styles;
    }

    public function addStyle(int $col, Style $style)
    {
        $this->styles[$col][] = $style;
    }
}