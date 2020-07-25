<?php

namespace Terminal;

class Terminal {

    private array $screens = [];
    private array $cursorPos = ["x" => 0, "y" => 0];

    public function __construct(?string $file = null)
    {
        if (null !== $file) {
            $data = @file_get_contents($file);
            if (!empty($data)) {
                $this->parseScreens($data);
            }
        }
    }

    /**
     * Parse the screens from ttyrec file contents
     * @param string $contents
     */
    private function parseScreens(string $contents)
    {
        while(strlen($contents) > 0) {
            $data = unpack('Vsec/Vusec/Vlen', $contents);
            $contents = substr($contents, 12);
            $screen = substr($contents, 0, $data["len"]);
            $this->setScreen($data["sec"], $data["usec"], $data["len"], $screen);
            $contents = substr($contents, $data["len"]);
        }
    }

    private function setScreen($sec, $usec, $len, $screen){
        $this->screens[] = new Screen($sec, $usec, $len, $screen);
    }

    public function getScreens(){
        return $this->screens;
    }

    /**
     * @param bool $actual - run with actual timestamps
     * @param int $timeoutInMicros - constant delay between frames quarter of a second
     */
    public function printScreens(bool $actual = false, int $timeoutInMicros = 250000)
    {
        $prevScreen = null;
        /** @var Screen $screen */
        foreach ($this->screens as $screen) {
            if ($actual && null !== $prevScreen) {
                $timeoutInMicros = $this->calculateDiffBetweenScreens($screen, $prevScreen);
            }
            usleep($timeoutInMicros);
            print ($screen->screen);
            $prevScreen = $screen;
        }
    }

    private function calculateDiffBetweenScreens(Screen $screen, Screen $previousScreen)
    {
        return (1000000 * ($screen->sec - $previousScreen->sec)) + ($screen->usec - $previousScreen->usec);
    }
}
