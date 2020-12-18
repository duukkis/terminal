<?php
namespace Terminal\Commands;

class ColorCommand extends Command
{
    const BLACK = '000000';
    const RED = 'FF0000';
    const GREEN = '00FF00';
    const YELLOW = 'FFFF00';
    const BLUE = '0000FF';
    const MAGENTA = 'FF00FF';
    const CYAN = '00FFFF';
    const WHITE = 'FFFFFF';

    public string $color;
    public bool $background;

    public function __construct(string $color, bool $background, string $output)
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

    public function colorToRGB(): array
    {
        $splitted = str_split($this->color, 2);
        return ["r" => hexdec($splitted[0]), "g" => hexdec($splitted[1]), "b" => hexdec($splitted[2])];
    }
}