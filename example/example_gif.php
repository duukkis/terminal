<?php
require __DIR__ . '/../vendor/autoload.php';

use Terminal\TerminalToGif;
use Gif\GifEncoder;

$file = __DIR__."/game.ttyrec";

$terminalToGif = new TerminalToGif($file);
// write three files
$terminalToGif->screenToGif(6415, "test.gif");
$terminalToGif->screenToGif(6414, "test2.gif");
$terminalToGif->screenToGif(6410, "test3.gif");

// make animated gif from them
$gifEncoder = new GifEncoder(["test3.gif", "test2.gif", "test.gif"], [100, 100, 200], 1, 2, "url");
$gifEncoder->writeGif("animated.gif");
