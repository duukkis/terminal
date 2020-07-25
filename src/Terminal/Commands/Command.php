<?php
namespace Terminal\Commands;

abstract class Command
{
    public string $output = "";

    protected function __construct($output)
    {
        $this->output = $output;
    }

    public function getOutput()
    {
        return $this->output;
    }
}