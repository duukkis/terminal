<?php

namespace Terminal\Style;

class ColorStyle extends Style
{
    public int $red;
    public int $green;
    public int $blue;
    private bool $background;

    public function __construct(int $row, int $col, int $red, int $green, int $blue, bool $background)
    {
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
        $this->background = $background;
        parent::__construct($row, $col);
    }
}