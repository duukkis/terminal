<?php
namespace Terminal\Commands;

class ClearScreenCommand extends Command
{

    public function __construct(string $output)
    {
        parent::__construct($output);
    }

}