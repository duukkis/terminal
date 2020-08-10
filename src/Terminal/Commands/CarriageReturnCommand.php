<?php
namespace Terminal\Commands;

class CarriageReturnCommand extends Command
{
    public function __construct(string $output)
    {
        parent::__construct($output);
    }

}