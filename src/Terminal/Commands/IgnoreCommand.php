<?php
namespace Terminal\Commands;

class IgnoreCommand extends Command
{
    public string $description = "";

    public function __construct(string $output, string $description)
    {
        $this->description = $description;
        parent::__construct($output);
    }

}