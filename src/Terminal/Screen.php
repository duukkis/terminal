<?php

namespace Terminal;

class Screen
{
    public int $sec;
    public int $usec;
    public int $len;
    public string $screen;
    public array $commands;

    public function __construct(int $sec, int $usec, int $len, string $screen)
    {
        $this->sec = $sec;
        $this->usec = $usec;
        $this->len = $len;
        $this->screen = $screen;
        $this->commands = Interpret::interpret($screen);
    }

    public function getCommands()
    {
        return $this->commands;
    }
}