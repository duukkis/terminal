<?php
namespace Terminal\Commands;

class ReverseVideoCommand extends Command
{
    public function __construct(string $output)
    {
        parent::__construct($output);
    }

}