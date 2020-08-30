<?php
namespace Terminal\Commands;

class ColorCommand256 extends Command
{
    // color 256
    public int $color;
    public bool $background;

    public function __construct(int $color, bool $background, string $output)
    {
        $this->color = $color;
        $this->background = $background;
        parent::__construct($output);
    }

    public function isForeground()
    {
        return !$this->isBackground();
    }

    public function isBackground()
    {
        return $this->background;
    }
}