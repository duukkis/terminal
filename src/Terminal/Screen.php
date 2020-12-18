<?php

namespace Terminal;

class Screen
{
    public int $sec;
    public int $usec;
    public int $len;
    public string $screen;
    public array $commands;
    // console presenting the console in Terminal
    public Console $console;

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

    public function setConsole(Console $console): void
    {
        $this->console = $console;
    }

    public function getConsole(): Console
    {
        return $this->console;
    }
}