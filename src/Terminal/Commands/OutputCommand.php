<?php
namespace Terminal\Commands;

class OutputCommand extends Command
{

    public function __construct(string $output)
    {
        parent::__construct($output);
    }
}