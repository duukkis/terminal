<?php
namespace Terminal\Commands;

class AddStyleCommand extends Command
{
    private string $style;

    const BOLD = 'bold';
    const UNDERLINE = 'underline';
    const REVERSE = 'reverse';

    public function __construct(string $output, string $style)
    {
        $this->style = $style;
        parent::__construct($output);
    }

    public function getStyle(): string
    {
        return $this->style;
    }
}