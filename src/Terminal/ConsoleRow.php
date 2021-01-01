<?php

namespace Terminal;

use Terminal\Style\Style;

class ConsoleRow
{
    public string $output;
    private array $styles = [];

    const MIN = 0;
    const MAX = 10000;

    public function __construct(string $output, array $styles = [])
    {
        $this->output = $output;
        $this->styles = $styles;
    }

    public function copy(): ConsoleRow
    {
        return new ConsoleRow($this->output, $this->styles);
    }

    public function getOutputTo(int $col): string
    {
        return substr($this->output, 0, $col);
    }

    public function setOutputTo(int $col): void
    {
        $this->output = substr($this->output, 0, $col);
    }

    public function getOutputFrom(int $col): string
    {
        return substr($this->output, $col);
    }

    public function isEmpty(): bool
    {
        return (empty($this->output) && empty($this->styles));
    }

    public function getStyles(?int $before = self::MAX, ?int $after = self::MIN): array
    {
        return array_filter($this->styles, function($k) use ($before, $after) {
            return ($k >= $after && $k <= $before);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function setStyles(?int $before = self::MAX, ?int $after = self::MIN): void
    {
        $this->styles = array_filter($this->styles, function($k) use ($before, $after) {
            return ($k >= $after && $k <= $before);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function removeStyles(int $before)
    {
        $this->styles = $this->getStyles($before);
    }

    public function addStyle(int $col, Style $style)
    {
        $this->styles[$col][] = $style;
    }

    /**
     * returns style length. If we have colorStyle in col 1 and removeStyle in col 24, this returns 23
     * @return array
     */
    public function getStyleLengths(): array
    {
        $result = [];
        $previousSettedIndex = null;
        $settedIndexes = array_keys($this->styles);
        if (!empty($settedIndexes)) {
            sort($settedIndexes);
            foreach ($settedIndexes as $index) {
                if ($previousSettedIndex == null) {
                    $previousSettedIndex = $index;
                } else {
                    $result[$previousSettedIndex] = ($index - $previousSettedIndex);
                    $previousSettedIndex = $index;
                }
            }
            $result[$previousSettedIndex] = 0;
        }
        return $result;
    }
}