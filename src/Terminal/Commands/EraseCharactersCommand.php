<?php
namespace Terminal\Commands;

class EraseCharactersCommand extends Command
{
    public function __construct(int $charsToRemove)
    {
        $output = str_pad("", $charsToRemove, " ");
        parent::__construct($output);
    }

}