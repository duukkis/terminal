<?php
namespace Terminal\Commands;

class ClearLineCommand extends Command
{
    public bool $right = false;
    public bool $left = false;

    public function __construct($output, bool $right, bool $left)
    {
        $this->right = $right;
        $this->left = $left;
        parent::__construct($output);
    }

}