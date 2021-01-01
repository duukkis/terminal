<?php

namespace Terminal\Commands;

class AddStyleCommand extends Command
{
    private string $style;

    const BOLD = 'bold';
    const UNDERLINE = 'underline';
    const REVERSE = 'reverse';
    const BLINK = 'blink';
    const INVISIBLE = 'invisible';

    const ALLOWED = [self::BOLD, self::UNDERLINE, self::REVERSE, self::BLINK, self::INVISIBLE];

    /**
     * AddStyleCommand constructor.
     * @param string $output
     * @param string $style
     * @throws \InvalidArgumentException
     */
    public function __construct(string $output, string $style)
    {
        if (!in_array($style, self::ALLOWED)) {
            throw new \InvalidArgumentException($style);
        }
        $this->style = $style;
        parent::__construct($output);
    }

    public function getStyle(): string
    {
        return $this->style;
    }
}
