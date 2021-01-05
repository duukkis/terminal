<?php
namespace Terminal\Commands;

class RemoveStyleCommand extends Command
{
    public function __construct(string $output)
    {
        parent::__construct($output);
    }

}