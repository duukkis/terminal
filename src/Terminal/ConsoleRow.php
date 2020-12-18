<?php

namespace Terminal;

class ConsoleRow
{
    public string $output;

    public function __construct(string $output)
    {
        $this->output = $output;
    }

    public function getOutputTo(int $col)
    {
        return substr($this->output, 0, $col);
    }

    public function getOutputFrom(int $col)
    {
        return substr($this->output, $col);
    }

    public function isEmpty(): bool
    {
        return empty(trim($this->output));
    }
}