<?php

namespace Gif;

/*
 *  GIFEncoder Version 2.0 by László Zsidi
 *  https://github.com/jacoka/GIFEncoder/blob/master/GIFEncoder.class.php
 *  Modifications by Duukkis
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * GIF89a is https://en.wikipedia.org/wiki/GIF
 * byte#  hexadecimal  text or
 * (hex)               value         Meaning
 * 0:     47 49 46
 *        38 39 61     GIF89a     Header
 *                                Logical Screen Descriptor
 * 6:     90 01        400        - width in pixels
 * 8:     90 01        400        - height in pixels
 * A:     F7                      - GCT follows for 256 colors with resolution 3 x 8bits/primary
 * B:     00           0          - background color #0
 * C:     00                      - default pixel aspect ratio
 * D:                            Global Color Table
 * :
 * 30D:   21 FF                  Application Extension block
 * 30F:   0B           11         - eleven bytes of data follow
 * 310:   4E 45 54
 *        53 43 41
 *        50 45        NETSCAPE   - 8-character application name
 *        32 2E 30     2.0        - application "authentication code"
 * 31B:   03           3          - three more bytes of data
 * 31C:   01           1          - data sub-block index (always 1)
 * 31D:   FF FF        65535      - unsigned number of repetitions
 * 31F:   00                      - end of App Extension block
 * 320:   21 F9                  Graphic Control Extension for frame #1
 * 322:   04           4          - four bytes of data follow
 * 323:   08                      - bit-fields 3x:3:1:1, 000|010|0|0 -> Restore to bg color
 * 324:   09 00                   - 0.09 sec delay before painting next frame
 * 326:   00                      - no transparent color
 * 327:   00                      - end of GCE block
 * 328:   2C                     Image Descriptor
 * 329:   00 00 00 00  (0,0)      - NW corner of frame at 0, 0
 * 32D:   90 01 90 01  (400,400)  - Frame width and height: 400 × 400
 * 331:   00                      - no local color table; no interlace
 * 332:   08           8         LZW min code size
 * 333:   FF           255       - 255 bytes of LZW encoded image data follow
 * 334:                data
 * 433:   FF           255       - 255 bytes of LZW encoded image data follow
 * data
 * :
 * 92BA:  00                    - end of LZW data for this frame
 * 92BB:  21 F9                 Graphic Control Extension for frame #2
 * :                                                            :
 * 153B7B:21 F9                 Graphic Control Extension for frame #44
 * :
 * 15CF35:3B                    File terminator
 *
 * And that what we are creating here from existing gifs
 */

use Exception;

class AnimatedGif {

    const GCT_POSITION = 10;
    const COLORTABLE_POSITION = 13;
    // GIF header 6 bytes
    const GIF89a = "GIF89a";
    const GRAPHIC_CONTROL_START = "\x21\xF9\x04";
    const GIF_HEADER = "!\377\13NETSCAPE2.0\3\1";
    const ZERO_BYTE = "\x0";

    private string $gif = self::GIF89a;
    private int $loops;
    private array $gifSources;
    private array $gifDelays;
    /*
        Disposal Methods:
        000: Not specified - 0
        001: Do not dispose - 1
        010: Restore to BG color - 2
        011: Restore to previous - 3
    */
    private int $disposalMethod;

    // set transparent as white for now
    private int $transRed = 255;
    private int $transGreen = 255;
    private int $transBlue = 255;
    private string $transparentColor = '';

    private int $numberOfColorsInFirstFrame = 0;
    private string $firstFrameColorRgbTable = "";
    private int $firstFrameEndian;

    /*
     * @param array $gifSources - sources
     * @param array $gifDelays - delays
     * @param int $loops - loops
     * @param array $disposalMethod - see above
    */
    public function __construct(
        array $gifSources = [],
        array $gifDelays = [],
        int $loops = 0,
        ?int $disposalMethod = 2
    ) {
        $disposalMethod = (null !== $disposalMethod) ? $disposalMethod : 2;

        $this->loops = abs($loops);
        $this->disposalMethod = (in_array($disposalMethod, [0,1,2,3])) ? $disposalMethod : 2;

        if (empty($gifDelays)) {
            exit("Need a delay");
        }

        $this->transparentColor = chr($this->transRed).chr($this->transGreen).chr($this->transBlue);
        $this->gifSources = $gifSources;
        $this->gifDelays = $gifDelays;

    }

    /**
     * write the animated gif
     * @param string $fileName
     */
    public function write(string $fileName)
    {
        $this->openFileForWriting($fileName);

        $firstFrame = null;
        $delay = 30;
        $index = 0;
        foreach($this->gifSources as $gif) {
            $frame = $this->loadGif($gif);
            if (null !== $frame){
                // set the first frame
                if (null === $firstFrame) {
                    $firstFrame = $frame;
                    //  set the first frame items to be reused
                    $this->setFirstFrame($frame);
                    $this->addGifHeader($firstFrame);
                }
                $delay = (int) (isset($this->gifDelays[$index])) ? $this->gifDelays[$index] : $delay;

                $this->addFrameToGif($frame, $delay);

                $this->cleanBufferToFile();
                $index++;
            }
        }
        $this->addGifFooter();

        $this->closeFileForWriting();
    }

    /**
     * set these so we don't have to do it in every loop
     * @param string $firstFrame
     */
    private function setFirstFrame(string $firstFrame): void
    {
        $this->numberOfColorsInFirstFrame = $this->getNumberOfColors($firstFrame);
        $this->firstFrameColorRgbTable = substr($firstFrame, self::COLORTABLE_POSITION, $this->getGCTLength($firstFrame));
        $this->firstFrameEndian = (ord ($firstFrame[self::GCT_POSITION]) & 0x07);
    }

    private function loadGif(string $fileName): ?string
    {
        if (IMAGETYPE_GIF == exif_imagetype($fileName)) {
            $f = fopen($fileName, "rb");
            $resource = fread($f, filesize($fileName));
            fclose($f);
            return $resource;
        }
        return null;
    }

    /**
     * directly to file writing functions to decrease resource usage and speed up everything
     */
    private $fileBuffer = null;

    private function openFileForWriting(string $filename): void
    {
        try {
            $this->fileBuffer = fopen($filename, 'w');
        } catch (Exception $exception) {
            print($exception->getMessage());
            die("Cannot open " . $filename . " for writing.");
        }
    }

    private function cleanBufferToFile(): void
    {
        fwrite($this->fileBuffer, $this->gif);
        $this->gif = '';
    }

    private function closeFileForWriting(): void
    {
        $this->cleanBufferToFile();
        fclose($this->fileBuffer);
    }

    /*
     * Animated Gif consists
     *   8-character application name (NETSCAPE)
     *   application "authentication code" (2.0)
     *   three more bytes of data 3
     *   data sub-block index (always 1)
     *   unsigned number of repetitions
     *   end of App Extension block \0
     */
    private function addGifHeader(string $firstFrame) {
        // here we copy from the first frame width and height and Global Color Table specification
        // to animated gif
        // the lowest 3 bits represent the bit depth minus 1, the highest true bit means that the GCT is present
        if ($this->isGCTPresent($firstFrame)) {
            // get GCT map follows for 256 colors with resolution 3 × 8 bits/primary
            $gctLength = $this->getGCTLength($firstFrame);
            $this->gif .= substr($firstFrame, 6, 7); // width and height from first image
            $this->gif .= substr($firstFrame, self::COLORTABLE_POSITION, $gctLength);  // color map
            $this->gif .= self::GIF_HEADER . $this->numbersToTwoBit($this->loops) . self::ZERO_BYTE;
        }
    }

    /**
     * checks the highest bit from GCT field
     * @param string $frame
     * @return bool
     */
    private function isGCTPresent(string $frame): bool
    {
        return (ord($frame[self::GCT_POSITION]) & 0x80);
    }

    /**
     * each color is presented in 3 bit series
     * @param string $frame
     * @return int
     */
    private function getGCTLength(string $frame): int
    {
        return 3 * $this->getNumberOfColors($frame);
    }

    private function getNumberOfColors(string $frame): int
    {
        return (2 << (ord($frame[self::GCT_POSITION]) & 0x07));
    }

    private function numbersToTwoBit($numb): string
    {
        return ( chr($numb & 0xFF) . chr(($numb >> 8) & 0xFF));
    }

    /*
     * add frame to gif
     * Adds the following into gif
     * 320:   21 F9                  Graphic Control Extension for frame #1
     * 322:   04           4          - four bytes of data follow
     * 323:   08                      - bit-fields 3x:3:1:1, 000|010|0|0 -> Restore to bg color
     * 324:   09 00                   - 0.09 sec delay before painting next frame
     * 326:   00                      - no transparent color
     * 327:   00                      - end of GCE block
     * 328:   2C                     Image Descriptor
     * 329:   00 00 00 00  (0,0)      - NW corner of frame at 0, 0
     * 32D:   90 01 90 01  (400,400)  - Frame width and height: 400 × 400
     * 331:   00                      - no local color table; no interlace
     * 332:   08           8         LZW min code size
     * 333:   FF           255       - 255 bytes of LZW encoded image data follow
     * 334:                data
     */
    private function addFrameToGif(string $frame, int $frameDuration)
    {
        $gctLength = $this->getGCTLength($frame);
        $frame_start = 13 + $gctLength; // remove header data
        $frame_end = strlen($frame) - $frame_start - 1; // -1 so we dont take in the gif ender ; \x3B
        // if local rgb is same as global we remove em so we separate it from frame for possible later use
        $frameColorRgbTable = substr($frame, self::COLORTABLE_POSITION, $gctLength);
        $frameImageData = substr($frame, $frame_start, $frame_end);
        $numberOfColorsInGCT = $this->getNumberOfColors($frame);
        // start of frame n
        // 21 F9 04
        // - bit-fields 3x:3:1:1, 000|010|0|0 -> Restore to bg color
        // - 0.09 sec delay before painting next frame
        // \x0 marking no transparent color \x0 marking end of GCE block
        $frameGraphicControlExtension =
            self::GRAPHIC_CONTROL_START .
            chr(($this->disposalMethod << 2) + 0) .
            $this->numbersToTwoBit($frameDuration) .
            self::ZERO_BYTE . self::ZERO_BYTE;

        // in frame there is a gct block
        if ($this->isGCTPresent($frame)) {
            // find the frames transparent color and set it to header as transparent color
            for ($j = 0; $j < $numberOfColorsInGCT; $j++) {
                $index = 3 * $j;
                // find the transparent color index and set it to frame header with chr($j)
                if (substr($frameColorRgbTable, $index, 3) == $this->transparentColor) {
                    $frameGraphicControlExtension =
                        self::GRAPHIC_CONTROL_START .
                        chr(($this->disposalMethod << 2) + 1) .
                        $this->numbersToTwoBit($frameDuration) .
                        chr($j) .
                        self::ZERO_BYTE;
                }
            }
        }

        // we remove the rgb in between so we can possibly add it in between
        // keep the image descriptor from frame
        // * 328:   2C                     Image Descriptor
        // * 329:   00 00 00 00  (0,0)      - NW corner of frame at 0, 0
        // * 32D:   90 01 90 01  (400,400)  - Frame width and height: 400 × 400
        // * 331:   00                      - no local color table; no interlace
        // we switch the last byte on the next if, if there is a local color table
        $frameImageDescriptor = substr($frameImageData, 0, self::GCT_POSITION);
        $frameImageData = substr($frameImageData, self::GCT_POSITION, strlen($frameImageData) - self::GCT_POSITION);

        // if there is a transparent color in frame
        // and if local and global frame length differ
        // and color tables are different
        if ($this->isGCTPresent($frame) &&
            ($this->numberOfColorsInFirstFrame != $numberOfColorsInGCT || $this->firstFrameColorRgbTable != $frameColorRgbTable)) {

            $byte = ord($frameImageDescriptor[9]);
            $byte |= 0x80;
            $byte &= 0xF8;
            $byte |= $this->firstFrameEndian;
            $frameImageDescriptor[9] = chr($byte);
        } else {
            // do not append frame rgb since the frame is same as first frame
            $frameColorRgbTable = '';
        }
        $this->gif .= $frameGraphicControlExtension . $frameImageDescriptor . $frameColorRgbTable . $frameImageData;
    }

    /**
     * adds the file terminator 3B = ;
     * 3B                    File terminator
     */
    private function addGifFooter(): void
    {
        $this->gif .= "\x3B";
    }
}
