<?php
namespace Terminal\Commands;

class BackspaceCommand extends Command
{
    public function __construct(string $output)
    {
        parent::__construct($output);
    }

}