<?php
namespace Terminal\Commands;

class MoveCursorHomeCommand extends Command
{
    public function __construct(string $output)
    {
        parent::__construct($output);
    }

}