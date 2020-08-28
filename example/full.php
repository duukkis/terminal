<?php
require __DIR__ . '/../vendor/autoload.php';

use Terminal\TerminalToGif;
use Terminal\Terminal;
use Gif\AnimatedGif;

$file = __DIR__."/game.ttyrec";

$terminalToGif = new TerminalToGif($file);
$terminalToGif->setFgColor(0, 0, 0);
$terminal = $terminalToGif->getTerminal();
$screens = $terminal->getScreens();

$gifs = [];
$onedelay = [5];
$terminate = false;
$terminatedOnScreen = 0;

for ($i = 1;$i < $terminal->numberOfScreens();$i++) {
    $fileName = "temp/".$i.".gif";
    if (!file_exists($fileName) && $terminatedOnScreen == 0) {
        $terminalToGif->screenToGif($i, $fileName);
    }
    if (strpos($screens[$i]->screen, "killed by") !== false
       && strpos($screens[$i]->screen, "You died") !== false) {
        // stop on the screen that has "killed by" and "You died"
        $terminatedOnScreen = $i;
    }
    if ($terminatedOnScreen == 0) {
        $gifs[] = $fileName;
    }
}
print "gifs written".PHP_EOL;

$durationInSeconds = $terminal->getDurationInSeconds();

// scale duration into seconds
$scaleDurationToSeconds = 120;
$ratio = $scaleDurationToSeconds / $durationInSeconds;

$delays = [];
for ($i = 2;$i < $terminal->numberOfScreens();$i++) {
    // hundred's of a second
    $delays[] = max(round(Terminal::calculateDiffBetweenScreens($screens[$i], $screens[$i-1]) * $ratio / 10000), 1);
}

$endResult = "animated.gif";

/*
 * this takes 63 seconds
 */

// $a = time();
// make animated gif from all
// $gifEncoder = new GifEncoder($gifs, $onedelay, 1, 2, "url");
// $gifEncoder->writeGif($endResult);
// $b = time();
// print "took ".($b-$a)." seconds".PHP_EOL;  // takes roughly 63 seconds

$a = time();
// clear the result directly into buffer
$gif = new AnimatedGif($gifs, $delays, 1, 2);
$gif->write($endResult);
$b = time();
print "took ".($b-$a)." seconds".PHP_EOL; // takes 4 seconds

print "animated gif done".PHP_EOL;

// remove the gifs
for ($i = 1;$i <= 6415;$i++) {
    $fileName = "temp/" . $i . ".gif";
    if (file_exists($fileName)) {
        // unlink($fileName);
    }
}

print "done".PHP_EOL;

// make a movie out of animated gif with
// ffmpeg -i animated.gif -movflags faststart -pix_fmt yuv420p -vf "scale=trunc(iw/2)*2:trunc(ih/2)*2" video.mp4