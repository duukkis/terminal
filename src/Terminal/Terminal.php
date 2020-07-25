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

    public function printScreens(int $timeoutInMicros = 100000)
    {
        /** @var Screen $screen */
        foreach ($this->screens as $screen) {
            print ($screen->screen);
            usleep($timeoutInMicros);
        }
    }
}