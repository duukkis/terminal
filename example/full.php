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
    if ($terminatedOnScreen == 0) {
        $gifs[] = $fileName;
    }
    if (strpos($screens[$i]->screen, "killed by") !== false
       && strpos($screens[$i]->screen, "You died") !== false) {
        // stop on the screen that has "killed by" and "You died"
        $terminatedOnScreen = $i;
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
        unlink($fileName);
    }
}

print "done".PHP_EOL;

// make twitter compatible movie out of animated gif with

# ffmpeg -i animated.gif -pix_fmt yuv420p -vcodec libx264 -vf scale=640:-1 -acodec aac -vb 1024k -minrate 1024k -maxrate 1024k -bufsize 1024k -ar 44100  -ac 2  -strict experimental -r 30  out.mp4
#------------------------------------ find dimenions with
# ffprobe -v error -show_format -show_streams out.mp4
#------------------------------------ then shorten the video to 2 min with 
# ffmpeg -i out.mp4 -filter:v "setpts=(120/649)*PTS" output.mp4
#------------------------------------ rerun the twitter compatibility filter
# ffmpeg -i output.mp4 -pix_fmt yuv420p -vcodec libx264 -vf scale=640:-1 -acodec aac -vb 1024k -minrate 1024k -maxrate 1024k -bufsize 1024k -ar 44100  -ac 2  -strict experimental -r 30 twitter.mp4
