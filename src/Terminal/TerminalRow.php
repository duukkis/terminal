<?php

namespace Terminal;

class TerminalRow
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
}