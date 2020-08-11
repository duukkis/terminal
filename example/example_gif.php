<?php
require __DIR__ . '/../vendor/autoload.php';

use Terminal\TerminalToGif;

$file = __DIR__."/game.ttyrec";

$terminalToGif = new TerminalToGif($file);
$terminalToGif->screenToGif(6415, "test.gif");
