<?php
namespace Terminal\Commands;

class MoveArrowCommand extends Command
{
    public bool $right;
    public bool $left;
    public bool $up;
    public bool $down;

    public function __construct(bool $up, bool $down, bool $right, bool $left, string $output = "")
    {
        $this->right = $right;
        $this->left = $left;
        $this->up = $up;
        $this->down = $down;
        parent::__construct($output);
    }

}