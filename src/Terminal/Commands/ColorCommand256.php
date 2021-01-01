<?php
namespace Terminal\Commands;

class ColorCommand256 extends Command
{
    // color 256
    public int $color;
    public bool $background;
    const COLORS0TO15 = [
        0 => '000000',
        1 => '800000',
        2 => '008000',
        3 => '808000',
        4 => '000080',
        5 => '800080',
        6 => '008080',
        7 => 'c0c0c0',
        8 => '808080',
        9 => 'ff0000',
        10 => '00ff00',
        11 => 'ffff00',
        12 => '0000ff',
        13 => 'ff00ff',
        14 => '00ffff',
        15 => 'ffffff',
    ];
    const COLORS_GRAYSCALE = [
        232 => '000000',
        233 => '121212', // 18
        234 => '1c1c1c', // 28
        235 => '262626',
        236 => '303030',
        237 => '3a3a3a',
        238 => '444444',
        239 => '4e4e4e',
        240 => '585858',
        241 => '626262',
        242 => '6c6c6c',
        243 => '767676',
        244 => '808080',
        245 => '8a8a8a',
        246 => '949494',
        247 => '9e9e9e', // 158
        248 => 'a8a8a8', // 168
        249 => 'b2b2b2',
        250 => 'bcbcbc',
        251 => 'c6c6c6',
        252 => 'd0d0d0',
        253 => 'dadada',
        254 => 'e4e4e4',
        255 => 'eeeeee', // 238
    ];

    public function __construct(int $color, bool $background, string $output)
    {
        if ($color < 0 || $color > 255) {
            throw new \InvalidArgumentException($color);
        }
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

    /**
     * see https://jonasjacek.github.io/colors/
     * @return array|int[]
     */
    public function colorToRGB(): array
    {
        $i = $this->color;
        if ($i < 16) {
            $splitted = str_split(self::COLORS0TO15[$i], 2);
            return ["r" => hexdec($splitted[0]), "g" => hexdec($splitted[1]), "b" => hexdec($splitted[2])];
        } else if ($i > 231){
            $splitted = str_split(self::COLORS_GRAYSCALE[$i], 2);
            return ["r" => hexdec($splitted[0]), "g" => hexdec($splitted[1]), "b" => hexdec($splitted[2])];
        } else { // map to 216 colors
            $i = $i - 16;
            $b = $i % 6;
            $b = (int) ($b > 0) ? ($b - 1) * 40 + 95 : 0;

            $i = floor($i / 6);
            $g = $i % 6;
            $g = (int) ($g > 0) ? ($g - 1) * 40 + 95 : 0;

            $i = floor($i / 6);
            $r = $i % 6;
            $r = (int) ($r > 0) ? ($r - 1) * 40 + 95 : 0;
            return ["r" => $r, "g" => $g, "b" => $b];
        }
    }

    public function colorToHexCode(): string
    {
        $i = $this->color;
        if ($i < 16) {
            return self::COLORS0TO15[$i];
        } else if ($i > 231){
            return self::COLORS_GRAYSCALE[$i];
        } else {
            $colors = $this->colorToRGB();
            return sprintf('%02s%02s%02s', dechex($colors[0]), dechex($colors[1]), dechex($colors[2]));
        }
    }
}