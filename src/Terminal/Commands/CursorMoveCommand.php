<?php
namespace Terminal\Commands;

class CursorMoveCommand extends Command
{
    public ?int $col; // x
    public ?int $row; // y

    public function __construct(?int $row, ?int $col, string $output)
    {
        $this->row = $row;
        $this->col = $col;
        parent::__construct($output);
    }

}