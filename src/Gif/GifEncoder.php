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
    private int $disposal;
    var $transparentColor = -1;

    /*
     * @param array $GIF_src - sources
     * @param array $GIF_dly - delays
     * @param int $GIF_lop - loops
     * @param array $GIF_dis - disposal? - 2
     * @param string $GIF_mod - source type url / bin
    */
    public function __construct(
        array $gifSources = [],
        array $gifDelays = [],
        int $loops = 0,
        int $disposals = 2,
        string $fileType = "url"
    ) {
        $this->loops = abs($loops);
        $this->disposal = (in_array($disposals, [0,1,2])) ? $disposals : 2;
        // set transparent as white for now
        $this->transparentColor = ( 255 | ( 255 << 8 ) | ( 255 << 16 ));

        if (count($gifSources) !== count($gifDelays)) {
            exit("Sources dont match delays");
        }

        foreach($gifSources as $gif) {
            if ($fileType == "url") {
                $f = fopen($gif, "rb");
                $resource = fread($f, filesize($gif));
            } else if ($fileType == "bin") {
                $resource = $gif;
            } else {
                exit("File mod not defined");
            }

            $imageType = substr($resource, 0, 6);

            if (!in_array($imageType, ["GIF87a", "GIF89a"])){
                print $gif." is not a gif";
                exit();
            }
            $this->imageBuffer[] = $resource;
            // do not do additional checks - presume everything is ok
        }

        $this->addGifHeader($this->imageBuffer[0]);
        for ($i = 0; $i < count($this->imageBuffer); $i++ ) {
            $this->addFrameToGif($this->imageBuffer[$i], $gifDelays[$i]);
        }
        $this->addGifFooter();
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
        if (ord($this->imageBuffer[0][10]) & 0x80) {
            // GCT follows for 256 colors with resolution 3 × 8 bits/primary
            $cmap = 3 * ( 2 << ( ord ( $firstFrame[10]) & 0x07));
            $this->gif .= substr($firstFrame, 6, 7); // width and height from first image
            $this->gif .= substr($firstFrame, 13, $cmap);
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
    private function addFrameToGif($frame, $currentFrameLength) {

        $firstFrame = $this->imageBuffer[0];

        $frame_start = 13 + 3 * ( 2 << ( ord ( $frame[10]) & 0x07) );
        $frame_end = strlen($frame) - $frame_start - 1;
        // if local rgb is same as global we remove em
        $Locals_rgb = substr ($frame, 13,
            3 * ( 2 << (ord($frame[10]) & 0x07) ) );
        $Locals_tmp = substr($frame, $frame_start, $frame_end);

        $frameLen = 2 << (ord($frame[10]) & 0x07);

        $Global_len = 2 << (ord($firstFrame[10]) & 0x07);
        $Global_rgb = substr($firstFrame, 13,
            3 * ( 2 << (ord($firstFrame[10]) & 0x07) ) );

        // start of frame n
        // 21 F9
        // 4
        // - bit-fields 3x:3:1:1, 000|010|0|0 -> Restore to bg color
        // - 0.09 sec delay before painting next frame
        // \x0 marking no transparent color
        // \x0 marking end of GCE block
        $Locals_ext = "!\xF9\x04" . chr ( ( $this->disposal << 2 ) + 0 ) .
            chr ( ( $currentFrameLength >> 0 ) & 0xFF ) . chr ( ( $currentFrameLength >> 8 ) & 0xFF ) . "\x0\x0";

        // frame background color is not the same as the image we created
        // we switch the frames bg to what ever it is
        if ( $this->transparentColor > -1 && ord($frame[10]) & 0x80 ) {
            for ($j = 0; $j < ( 2 << ( ord ($frame[10]) & 0x07) ); $j++ ) {
                // if color is same as transparent
                if (
                    ord ( $Locals_rgb [3 * $j + 0]) == ( ( $this->transparentColor >> 16 ) & 0xFF ) &&
                    ord ( $Locals_rgb [3 * $j + 1]) == ( ( $this->transparentColor >> 8 ) & 0xFF ) &&
                    ord ( $Locals_rgb [3 * $j + 2]) == ( ( $this->transparentColor >> 0 ) & 0xFF )
                ) {
                    $Locals_ext = "!\xF9\x04" . chr ( ( $this->disposal << 2 ) + 1 ) .
                        chr ( ( $currentFrameLength >> 0 ) & 0xFF ) . chr ( ( $currentFrameLength >> 8 ) & 0xFF ) . chr ( $j ) . "\x0";
                    break;
                }
            }
        }

        // we remove the rgb in between so we can possibly add it in between
        $Locals_img = substr($Locals_tmp, 0, 10);
        $Locals_tmp = substr($Locals_tmp, 10, strlen($Locals_tmp) - 10);

        // if the local and global blocks colors differ, not first we need to add the frame color block
        if (ord ($frame[10]) & 0x80 &&
            !($Global_len == $frameLen && $this->compareRgbBlocks($Global_rgb, $Locals_rgb, $Global_len))) {
            $byte = ord($Locals_img [9]);
            $byte |= 0x80;
            $byte &= 0xF8;
            $byte |= (ord ($firstFrame[10]) & 0x07);
            $Locals_img [9] = chr($byte);
            $this->gif .= $Locals_ext . $Locals_img . $Locals_rgb . $Locals_tmp;
        } else {
            $this->gif .= $Locals_ext . $Locals_img . $Locals_tmp;
        }
    }

    /**
     * adds the file terminator 3B = ;
     * 3B                    File terminator
     */
    private function addGifFooter() {
        $this->gif .= ";";
    }

    /**
     * Compare global and frame rgb, returns true is same, false if different
     *
     * @param string $globalBlock
     * @param string $localBlock
     * @param integer $length
     *
     * @return bool
     */
    private function compareRgbBlocks($GlobalBlock, $LocalBlock, $Len): bool
    {
        for ( $i = 0; $i < $Len; $i++ ) {
            if (
                $GlobalBlock [3 * $i + 0] != $LocalBlock [3 * $i + 0] ||
                $GlobalBlock [3 * $i + 1] != $LocalBlock [3 * $i + 1] ||
                $GlobalBlock [3 * $i + 2] != $LocalBlock [3 * $i + 2]
            ) {
                return false;
            }
        }
        return true;
    }

    private function unsignedNumberOfRepetition($int): string
    {
        return ( chr ( $int & 0xFF ) . chr ( ( $int >> 8 ) & 0xFF ) );
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
