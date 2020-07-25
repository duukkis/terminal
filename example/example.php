<?php
require __DIR__ . '/../vendor/autoload.php';

use Terminal\Terminal;
use Terminal\Interpret;
use Terminal\Screen;


$file = __DIR__."/game.ttyrec";
$terminal = new Terminal($file);

if (true) {
    // speedy gonzales
    $terminal->printScreens(false, 1000);
    // quarter of a second delay between frames
    $terminal->printScreens();
    // with actual delay coded in ttyrec file
    $terminal->printScreens(true);
}

if (false) {
    $screens = $terminal->getScreens();
    /** @var Screen $screen */
    foreach ($screens as $screen) {
        print_r($screen->getCommands());
    }
}
