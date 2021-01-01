<?php

namespace Terminal;

use Terminal\Style\BoldStyle;
use Terminal\Style\ClearStyle;
use Terminal\Style\ColorStyle;
use Terminal\Style\ReverseStyle;
use Terminal\Style\Style;
use Terminal\Style\UnderlineStyle;

class TerminalToGif
{

    const NORMAL_FONT = 5;
    const BOLD_FONT = 6;
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

    /**
     * @param int $screenNumber
     * @param string $filename - filename to write the gif
     */
    public function screenToGifWithStyles(int $screenNumber, string $filename)
    {
        $screens = $this->terminal->getScreens();
        /** @var Screen $screen */
        $screen = $screens[$screenNumber];
        $this->console = $screen->getConsole();
        // init the image width and height
        $this->imageHeight = $this->rows * $this->fontHeight + (2 * $this->margin);
        $this->imageWidth = $this->cols * $this->fontWidth + (2 * $this->margin);
        $im = $this->createImageWithStyles();
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

    private function getColor($im, $r, $g, $b): ?int
    {
        return imagecolorallocate($im, $r, $g, $b);
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

    public function createImageWithStyles()
    {
        $im = imagecreate($this->imageWidth, $this->imageHeight);
        $bgcolor = $this->setBackgroundColor($im);
        $fgcolor = $this->getForegroundColor($im);
        $textcolor = $fgcolor;
        for ($i = 1; $i <= $this->rows; $i++) {
            /** @var ConsoleRow $row */
            $row = $this->console->getRow($i);
            if (null !== $row) {
                $text = $row->output;
                $x = $this->margin;
                $y = $i * $this->fontHeight + $this->margin;
                // first write the normal
                imagestring($im, $this->font, $x, $y, $text, $textcolor);
                // then write the colors on top of normals
                $styles = $row->getStyles();
                $styleLengths = $row->getStyleLengths();
                // print styles from col to col
                foreach ($styles as $col => $colstyles) {
                    /** @var Style $s */
                    foreach ($colstyles as $s){
                        $x = $this->margin + $col * $this->fontWidth;
                        $chr = substr($row->output, $col, $styleLengths[$col]);
                        switch (get_class($s)) {
                            case ColorStyle::class:
                                /** @var $s ColorStyle */
                                if ($s->red != $this->bgColor["r"] ||
                                    $s->green != $this->bgColor["g"] ||
                                    $s->blue != $this->bgColor["b"]) {
                                    $textcolor = $this->getColor($im, $s->red, $s->green, $s->blue);
                                }
                                break;
                            case BoldStyle::class:
                                /** @var $s BoldStyle */
                                $textcolor = $fgcolor;
                                $this->font = self::BOLD_FONT;
                                break;
                            case UnderlineStyle::class:
                                /** @var $s UnderlineStyle */
                                $textcolor = $fgcolor;
                                break;
                            case ReverseStyle::class:
                                /** @var $s ReverseStyle */
                                $textcolor = $bgcolor;
                                imagefilledrectangle(
                                    $im, $x, $y,
                                    $x + $this->fontWidth * $styleLengths[$col],
                                    $y + $this->fontHeight,
                                    $fgcolor
                                );
                                break;
                            case ClearStyle::class:
                                $this->font = self::NORMAL_FONT;
                                $textcolor = $fgcolor;
                                break;
                        }
                        if ($styleLengths[$col] > 0) {
                            imagestring($im, $this->font, $x, $y, $chr, $textcolor);
                        }
                    }
                }
            }
        }
        return $im;
    }

}
