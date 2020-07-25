<?php
require __DIR__ . '/../vendor/autoload.php';

use Terminal\Terminal;
use Terminal\Interpret;
use Terminal\Screen;


//print chr(0x1B).'[?1049h';
//die();
$file = __DIR__."/game.ttyrec";
$terminal = new Terminal($file);

$screens = $terminal->getScreens();
/** @var Screen $screen */
foreach ($screens as $screen) {
    print_r($screen->getCommands());
}
//$terminal->printScreens();

