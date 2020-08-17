<?php
require __DIR__ . '/../vendor/autoload.php';

use Terminal\TerminalToGif;
use Gif\AnimatedGif;

$file = __DIR__."/game.ttyrec";

$terminalToGif = new TerminalToGif($file);
// write three files
$terminalToGif->setFgColor(255, 0, 0);
$terminalToGif->screenToGif(6415, "test.gif");
$terminalToGif->setFgColor(0, 0, 0);
$terminalToGif->screenToGif(6414, "test2.gif");
$terminalToGif->setFgColor(0, 0, 255);
$terminalToGif->screenToGif(6410, "test3.gif");

// make animated gif from them
$animated = new AnimatedGif(["test3.gif", "test2.gif", "test.gif"], [100, 100, 200], 1, 2);
$animated->write("animated.gif");

