<?php

namespace Terminal;

use Terminal\Style\ClearStyle;
use Terminal\Style\Style;

class ConsoleRow
{
    public string $output;
    private array $styles;

    const MIN = 0;
    const MAX = 10000;

    public function __construct(string $output, array $styles = [])
    {
        $this->output = $output;
        $this->styles = $styles;
    }

    /**
     * Will make a deep-copy from row
     * @return ConsoleRow
     */
    public function copy(): ConsoleRow
    {
        // clean up while copying
        $allClear = true;
        foreach ($this->styles as $styles) {
            foreach ($styles as $style) {
                if (get_class($style) !== ClearStyle::class) {
                    $allClear = false;
                }
            }
        }
        if ($allClear) {
            $this->styles = [];
        }
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

    public function addStyle(int $col, Style $style)
    {
        $this->styles[$col][] = $style;
    }

    public function removeStyle(int $col, ClearStyle $style)
    {
        $this->styles[$col] = [];
        $this->styles[$col][] = $style;
    }

    /**
     * returns style length. If we have colorStyle in col 1 and removeStyle in col 24, this returns 23
     * @return array
     */
    public function getStyleLengths(): array
    {
        $result = [];
        $previousSetIndex = null;
        $setIndexes = array_keys($this->styles);
        if (!empty($setIndexes)) {
            sort($setIndexes);
            foreach ($setIndexes as $index) {
                if ($previousSetIndex == null) {
                    $previousSetIndex = $index;
                } else {
                    $result[$previousSetIndex] = ($index - $previousSetIndex);
                    $previousSetIndex = $index;
                }
            }
            $result[$previousSetIndex] = 0;
        }
        return $result;
    }
}