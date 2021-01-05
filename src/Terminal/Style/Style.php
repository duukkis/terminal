<?php

namespace Terminal\Style;

class Style
{
    private int $row;
    private int $screenNumber;

    public bool $colorSet = false;
    public int $red;
    public int $green;
    public int $blue;
    public bool $background = false;

    public bool $bold = false;
    public bool $underline = false;
    public bool $reverse = false;

    // the style is closed with removeStyleCommand
    public int $start = -1;
    private int $end = -1;

    public function __construct(int $row, int $start, int $screenNumber)
    {
        $this->row = $row;
        $this->start = $start;
        $this->screenNumber = $screenNumber;
    }

    public function setColor(int $red, int $green, int $blue, bool $background): void
    {
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
        $this->background = $background;
        $this->colorSet = true;
    }

    public function setBold(): void
    {
        $this->bold = true;
    }

    public function setUnderline(): void
    {
        $this->underline = true;
    }

    public function setReverse(): void
    {
        $this->reverse = true;
    }

    public function setEnd(int $end): void
    {
        $this->end = $end;
    }

    /**
     * @return int
     */
    public function getRow(): int
    {
        return $this->row;
    }

    public function getScreenNumber(): int
    {
        return $this->screenNumber;
    }

    public function getLength(): int
    {
        return ($this->end - $this->start);
    }

    public function isClosed(): bool
    {
        return ($this->end != -1);
    }
}