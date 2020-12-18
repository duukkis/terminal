<?php

namespace Terminal;

class TerminalToGif
{

    private Console $console;

    private int $font = 5;
    private int $fontWidth = 9;
    private int $fontHeight = 14;

    private $cols = 80;
    private $rows = 26;

    private $margin = 5;

    private $imageWidth = 0; // 730 with the above values
    private $imageHeight = 0; // 346 with the above values

    private $bgColor = ["r" => 255, "g" => 255, "b" => 255];
    private $fgColor = ["r" => 0, "g" => 0, "b" => 0];

    private Terminal $terminal;

    public function __construct(string $file)
    {
        $this->terminal = new Terminal($file);
        // load the screens and make em ready
        $this->terminal->loopScreens();
    }

    public function getTerminal(): Terminal
    {
        return $this->terminal;
    }

    // setters
    public function setFont($font, $fontWidth, $fontHeight)
    {
        $this->font = $font;
        $this->fontWidth = $fontWidth;
        $this->fontHeight = $fontHeight;
    }

    /**
     * @param int $r
     * @param int $g
     * @param int $b
     */
    public function setBgColor(int $r, int $g, int $b): void
    {
        $this->bgColor = ["r" => $r, "g" => $g, "b" => $b];
    }

    /**
     * @param int $r
     * @param int $g
     * @param int $b
     */
    public function setFgColor(int $r, int $g, int $b): void
    {
        $this->fgColor = ["r" => $r, "g" => $g, "b" => $b];
    }

    /**
     * @param int $margin
     */
    public function setMargin(int $margin): void
    {
        $this->margin = $margin;
    }

    /**
     * @param int $rows
     * @param int $cols
     */
    public function setDimensions(int $rows, int $cols): void
    {
        $this->rows = $rows;
        $this->cols = $cols;
    }
    /**
     * @param int $screenNumber
     * @param string $filename - filename to write the gif
     */
    public function screenToGif(int $screenNumber, string $filename)
    {
        $screens = $this->terminal->getScreens();
        /** @var Screen $screen */
        $screen = $screens[$screenNumber];
        $this->console = $screen->getConsole();
        // init the image width and height
        $this->imageHeight = $this->rows * $this->fontHeight + (2 * $this->margin);
        $this->imageWidth = $this->cols * $this->fontWidth + (2 * $this->margin);
        $im = $this->createImage();
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

    public function createImage()
    {
        $im = imagecreate($this->imageWidth, $this->imageHeight);
        $this->setBackgroundColor($im);
        $textcolor = $this->getForegroundColor($im);
        for ($i = 1; $i <= $this->rows; $i++) {
            $row = $this->console->getRow($i);
            if (null !== $row) {
                $x = $this->margin;
                $y = $i * $this->fontHeight + $this->margin;
                $text = $row->output;
                imagestring($im, $this->font, $x, $y, $text, $textcolor);
            }
        }
        return $im;
    }

}
