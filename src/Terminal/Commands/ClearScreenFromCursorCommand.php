<?php
namespace Terminal\Commands;

class ClearScreenFromCursorCommand extends Command
{

    public bool $down;
    public bool $up;

    public function __construct(bool $down, bool $up)
    {
        $this->down = $down;
        $this->up = $up;
    }

}