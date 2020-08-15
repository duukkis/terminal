<?php

namespace Gif;

/*
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

class GifEncoder {

    const GCT_POSITION = 10;
    const COLORTABLE_POSITION = 13;
    // GIF header 6 bytes
    const GIF89a = "GIF89a";
    const GRAPHIC_CONTROL_START = "\x21\xF9\x04";
    const GIF_HEADER = "!\377\13NETSCAPE2.0\3\1";
    const ZERO_BYTE = "\x0";

    private string $gif = self::GIF89a;
    private int $loops;
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
        ?int $disposalMethod = 2,
        ?string $writeGif = null
    ) {
        $disposalMethod = (null !== $disposalMethod) ? $disposalMethod : 2;

        $this->loops = abs($loops);
        $this->disposalMethod = (in_array($disposalMethod, [0,1,2,3])) ? $disposalMethod : 2;

        if (empty($gifDelays)) {
            exit("Need a delay");
        }

        $this->transparentColor = chr($this->transRed).chr($this->transGreen).chr($this->transBlue);

        if (null !== $writeGif) {
            $this->openFileForWriting($writeGif);
        }

        $firstFrame = null;
        $delay = 30;
        $index = 0;
        foreach($gifSources as $gif) {
            $resource = $this->openGif($gif);
            if (null !== $resource){
                // set the first fram
                if (null === $firstFrame) {
                    $firstFrame = $resource;
                    $this->addGifHeader($firstFrame);
                }
                $delay = (isset($gifDelays[$index])) ? $gifDelays[$index] : $delay;

                $this->addFrameToGif($resource, $delay, $firstFrame);

                if (null !== $writeGif) {
                    $this->cleanBufferToFile();
                }

                $index++;
            }
        }
        $this->addGifFooter();

        if (null !== $writeGif) {
            $this->closeFileForWriting();
        }
    }

    private function openGif($gif): ?string
    {
        if (IMAGETYPE_GIF == exif_imagetype($gif)) {
            $f = fopen($gif, "rb");
            $resource = fread($f, filesize($gif));
            fclose($f);
            return $resource;
        }
        return null;
    }

    /**
     * directly to file writing functions to decrease resource usage and speed up everything
     */
    private $fileBuffer = null;

    private function openFileForWriting($filename)
    {
        $this->fileBuffer = fopen($filename, 'w');
    }

    private function cleanBufferToFile()
    {
        fwrite($this->fileBuffer, $this->gif);
        $this->gif = '';
    }

    private function closeFileForWriting()
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
    private function addFrameToGif($frame, $currentFrameLength, $firstFrame) {

        $gctLength = $this->getGCTLength($frame);

        $frame_start = 13 + $gctLength;
        $frame_end = strlen($frame) - $frame_start - 1; // -1 we dont take in the gif ender ;

        // if local rgb is same as global we remove em so we separate it from frame
        $frameColorRgbTable = substr($frame, self::COLORTABLE_POSITION, $gctLength);
        $frameImageData = substr($frame, $frame_start, $frame_end);
        $numberOfColorsInGCT = $this->getNumberOfColors($frame);

        $numberOfColorsInFirstFrame = $this->getNumberOfColors($firstFrame);
        $firstFrameColorRgbTable = substr($firstFrame, self::COLORTABLE_POSITION, $this->getGCTLength($firstFrame));

        // start of frame n
        // 21 F9
        // 4
        // - bit-fields 3x:3:1:1, 000|010|0|0 -> Restore to bg color
        // - 0.09 sec delay before painting next frame
        // \x0 marking no transparent color
        // \x0 marking end of GCE block
        $frameGraphicControlExtension =
            self::GRAPHIC_CONTROL_START .
            chr(($this->disposalMethod << 2) + 0) .
            $this->numbersToTwoBit($currentFrameLength) .
            self::ZERO_BYTE . self::ZERO_BYTE;

        // in frame there is a gct block
        if ($this->isGCTPresent($frame)) {
            // find the frames transparent color and set it to header as transparent color
            // 30D:   21 F9                    Graphic Control Extension (comment fields precede this in most files)
            // 30F:   04           4            - 4 bytes of GCE data follow
            // 310:   01                        - there is a transparent background color
            // 311:   00 00                     - delay for animation in hundredths of a second
            // 313:   10          16            - color #16 is transparent
            // 314:   00                        - end of GCE block
            for ($j = 0; $j < $numberOfColorsInGCT; $j++) {
                $index = 3 * $j;
                // find the transparent color index and set it to frame header with chr($j)
                if (substr($frameColorRgbTable, $index, 3) == $this->transparentColor) {
                    $frameGraphicControlExtension =
                        self::GRAPHIC_CONTROL_START .
                        chr(($this->disposalMethod << 2) + 1) .
                        $this->numbersToTwoBit($currentFrameLength) .
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
            ($numberOfColorsInFirstFrame != $numberOfColorsInGCT || $firstFrameColorRgbTable != $frameColorRgbTable)) {

            $byte = ord($frameImageDescriptor[9]);
            $byte |= 0x80;
            $byte &= 0xF8;
            $byte |= (ord ($firstFrame[self::GCT_POSITION]) & 0x07);
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
    private function addGifFooter() {
        $this->gif .= "\x3B";
    }

    /**
     *
     * @return string
     */
    public function writeGif(string $filename): string
    {
        $fp = fopen($filename, "w+");
        fwrite($fp, $this->gif);
        fclose($fp);
        return $filename;
    }
}
