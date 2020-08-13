<?php

namespace Gif;

/*
 * ripped and rewrote from GIFEncoder Version 2.0 by László Zsidi
 * where nothing was explained
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

class GifEncoder {

    private string $gif = "GIF89a"; /* GIF header 6 bytes */

    private array $imageBuffer = [];
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

    /*
     * @param array $GIF_src - sources
     * @param array $GIF_dly - delays
     * @param int $GIF_lop - loops
     * @param array $GIF_dis - disposal? - 2 -- creates overlapping gifs if smaller than 2
     * @param string $GIF_mod - source type url / bin
    */
    public function __construct(
        array $gifSources = [],
        array $gifDelays = [],
        int $loops = 0,
        ?int $disposalMethod = 2,
        ?string $fileType = "url",
        ?string $writeGif = null
    ) {
        $disposalMethod = (null !== $disposalMethod) ? $disposalMethod : 2;

        $this->loops = abs($loops);
        $this->disposalMethod = (in_array($disposalMethod, [0,1,2,3])) ? $disposalMethod : 2;

        if (empty($gifDelays)) {
            exit("Need a delay");
        }

        if (null !== $writeGif) {
            $this->openFileForWriting($writeGif);
        }

        $firstFrame = null;
        $delay = 30;
        $index = 0;
        foreach($gifSources as $gif) {
            if ($fileType == "url") {
                $f = fopen($gif, "rb");
                $resource = fread($f, filesize($gif));
                fclose($f);
            } else if ($fileType == "bin") {
                $resource = $gif;
            } else {
                exit("File method not defined - need to be url or bin");
            }
            $imageType = substr($resource, 0, 6);
            if (!in_array($imageType, ["GIF87a"])) { // animated "GIF89a"
                print $gif." is not a gif";
                exit();
            }
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
        $this->addGifFooter();

        if (null !== $writeGif) {
            $this->closeFileForWriting();
        }
    }

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
        if (ord($firstFrame[10]) & 0x80) {
            // GCT follows for 256 colors with resolution 3 × 8 bits/primary
            $cmap = 3 * ( 2 << ( ord ( $firstFrame[10]) & 0x07));
            $this->gif .= substr($firstFrame, 6, 7); // width and height from first image
            $this->gif .= substr($firstFrame, 13, $cmap);  // color map
            $this->gif .= "!\377\13NETSCAPE2.0\3\1" . $this->unsignedNumberOfRepetition($this->loops) . "\0";
        }
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

        $frame_start = 13 + 3 * ( 2 << ( ord ($frame[10]) & 0x07) );
        $frame_end = strlen($frame) - $frame_start - 1;

        // if local rgb is same as global we remove em
        $frameColorRgbTable = substr($frame, 13, 3 * ( 2 << (ord($frame[10]) & 0x07) ) );
        $frameImageData = substr($frame, $frame_start, $frame_end);
        $frameLen = 2 << (ord($frame[10]) & 0x07);

        $firstFrameLength = 2 << (ord($firstFrame[10]) & 0x07);
        $firstFrameColorRgbTable = substr($firstFrame, 13, 3 * ( 2 << (ord($firstFrame[10]) & 0x07) ) );

        // start of frame n
        // 21 F9
        // 4
        // - bit-fields 3x:3:1:1, 000|010|0|0 -> Restore to bg color
        // - 0.09 sec delay before painting next frame
        // \x0 marking no transparent color
        // \x0 marking end of GCE block
        $frameGraphicControlExtension =
            "!\xF9\x04" .
            chr(($this->disposalMethod << 2) + 0) .
            chr(($currentFrameLength >> 0) & 0xFF) .
            chr(($currentFrameLength >> 8) & 0xFF ) .
            "\x0\x0";

        // in frame there is a transparent color
        if (ord($frame[10]) & 0x80) {
            // find the frames transparent color and set it to header as transparent color
            // 30D:   21 F9                    Graphic Control Extension (comment fields precede this in most files)
            // 30F:   04           4            - 4 bytes of GCE data follow
            // 310:   01                        - there is a transparent background color
            // 311:   00 00                     - delay for animation in hundredths of a second
            // 313:   10          16            - color #16 is transparent
            // 314:   00                        - end of GCE block
            for ($j = 0; $j < (2 << (ord($frame[10]) & 0x07)); $j++) {
                $index = 3 * $j;
                // find the transparent color index and set it to frame header
                if (
                    ord($frameColorRgbTable[$index + 0]) == $this->transRed &&
                    ord($frameColorRgbTable[$index + 1]) == $this->transGreen &&
                    ord($frameColorRgbTable[$index + 2]) == $this->transBlue
                ) {
                    $frameGraphicControlExtension =
                        "!\xF9\x04" .
                        chr(($this->disposalMethod << 2) + 1) .
                        chr(($currentFrameLength >> 0) & 0xFF) .
                        chr(($currentFrameLength >> 8) & 0xFF) .
                        chr($j) .
                        "\x0";
                    break;
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
        $frameImageDescriptor = substr($frameImageData, 0, 10);
        $frameImageData = substr($frameImageData, 10, strlen($frameImageData) - 10);

        // if there is a transparent color in frame
        // and if local and global frame length differ
        // and color tables are different
        if (ord ($frame[10]) & 0x80 ||
            $firstFrameLength !== $frameLen ||
            $firstFrameColorRgbTable != $frameColorRgbTable) {

            $byte = ord($frameImageDescriptor[9]);
            $byte |= 0x80;
            $byte &= 0xF8;
            $byte |= (ord ($firstFrame[10]) & 0x07);
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
        $this->gif .= ";";
    }

    private function unsignedNumberOfRepetition($loops): string
    {
        return ( chr($loops & 0xFF) . chr(($loops >> 8) & 0xFF));
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
