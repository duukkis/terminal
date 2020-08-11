<?php
require __DIR__ . '/../vendor/autoload.php';

use Terminal\Terminal;
use Terminal\Screen;

$file = __DIR__."/game.ttyrec";
// load the file
$terminal = new Terminal($file);

// you can print the screens with different timeouts
if (false) {
    // speedy gonzales
    $terminal->printScreens(false, 1000);
    // quarter of a second delay between frames
    $terminal->printScreens();
    // with actual delay coded in ttyrec file
    $terminal->printScreens(true);
}

// loop the screens and make txt files of all for debugging
if (false) {
    $terminal->loopScreens(true, true, null);
}

// goto screen 14 and print the output
if (false) {
    $console = $terminal->gotoScreen(14);
    print_r($console);
}

// or get the screen commands
if (false) {
    $screens = $terminal->getScreens();
    /** @var Screen $screen */
    foreach ($screens as $screen) {
        print_r($screen->getCommands());
    }
}
