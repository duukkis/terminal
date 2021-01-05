<?php

namespace Terminal;

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

    public function getStyles(): array
    {
        return $this->styles;
    }

    public function setStylesBefore(int $before): void
    {
        $this->styles = array_filter($this->styles, function($k) use ($before) {
            return ($k <= $before);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function setStylesAfter(int $after): void
    {
        $this->styles = array_filter($this->styles, function($k) use ($after) {
            return ($k >= $after);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function setBeforeAfterStyles(int $start, int $stop, int $screenNumber): void
    {
        foreach ($this->styles as $col => $arr) {
            if ($col >= $start && $col <= $stop) {
                /** @var Style $style */
                foreach ($this->styles[$col] as $i => $style) {
                    if ($style->getScreenNumber() != $screenNumber) {
                        unset($this->styles[$col][$i]);
                    }
                }
                if (!empty($this->styles[$col])) {
                    $this->styles[$col] = array_values($this->styles[$col]);
                } else {
                    unset($this->styles[$col]);
                }
            }
        }
    }

    public function clearStyles(): void
    {
        $this->styles = [];
    }

    public function addStyle(int $col, Style $style)
    {
        $this->styles[$col][] = $style;
    }
}