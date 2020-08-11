# terminal
ttyrec-file to something else.

- PHP 7.4
- Load composer https://getcomposer.org/
- composer install
- php example/example.php

```
// this so that composer autoload is loaded
require __DIR__ . './vendor/autoload.php';

use Terminal\Terminal;
use Terminal\Interpret;
use Terminal\Screen;

$file = __DIR__."/game.ttyrec";
$terminal = new Terminal($file);

// how to play the ttyrec file

// with actual delay coded in ttyrec file
$terminal->printScreens(true);
// speedy gonzales with small delay
$terminal->printScreens(false, 1000);
// quarter of a second delay between frames
$terminal->printScreens();


// if you wish to write the screens into files
// prequisite mkdir temp
$terminal->loopScreens(true);

// or print the screens / commands in screens
$screens = $terminal->getScreens();
/** @var Screen $screen */
foreach ($screens as $screen) {
    print_r($screen->getCommands());
}

```
