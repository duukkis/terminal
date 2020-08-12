<?php

namespace Terminal;

class TerminalToGif
{

    private $console = [];

    private int $font = 5;
    private $fontWidth = 9;
    private $fontHeight = 14;

    private $cols = 80;
    private $rows = 26;

    private $margin = 5;

    private $imageWidth = 0; // 730 with the top values
    private $imageHeight = 0; // 346 with the top values

    private $bgColor = ["r" => 255, "g" => 255, "b" => 255];
    private $fgColor = ["r" => 0, "g" => 0, "b" => 0];

    private Terminal $terminal;

    public function __construct(string $file)
    {
        $this->terminal = new Terminal($file);
        // load the screens and make em ready
        $this->terminal->loopScreens();
    }

    /**
     * @param int $screenNumber
     * @param string $filename - filename to write the gif
     */
    public function screenToGif(int $screenNumber, string $filename)
    {
        $screens = $this->terminal->getScreens();
        $screen = $screens[$screenNumber];
        $this->console = $screen->getConsole();
        // init the image width and height
        $this->imageHeight = $this->rows * $this->fontHeight + (2 * $this->margin);
        $this->imageWidth = $this->cols * $this->fontWidth + (2 * $this->margin);
        $im = $this->createImage($filename);
        imagegif($im, $filename);
        imagedestroy($im);
    }

    private function setBackgroundColor($im): ?int
    {
        return imagecolorallocate($im, $this->bgColor["r"], $this->bgColor["g"], $this->bgColor["b"]);
    }

    private function getForegroundColor($im): ?int
    {
        return imagecolorallocate($im, $this->fgColor["r"], $this->fgColor["g"], $this->fgColor["b"]);
    }

    public function createImage(string $filename)
    {
        $im = imagecreate($this->imageWidth, $this->imageHeight);
        $this->setBackgroundColor($im);
        $textcolor = $this->getForegroundColor($im);
        for ($i = 1; $i <= $this->rows; $i++) {
            if (isset($this->console[$i])) {
                $x = $this->margin;
                $y = $i * $this->fontHeight + $this->margin;
                $text = $this->console[$i]->output;
                imagestring($im, $this->font, $x, $y, $text, $textcolor);
            }
        }
        return $im;
    }

}
