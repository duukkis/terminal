<?php

namespace Terminal\Style;

class Style
{
    private int $row;
    private int $col;

    public function __construct(int $row, int $col)
    {
        $this->row = $row;
        $this->col = $col;
    }

    /**
     * @return int
     */
    public function getCol(): int
    {
        return $this->col;
    }

    /**
     * @return int
     */
    public function getRow(): int
    {
        return $this->row;
    }

    public function isSamePosition(Style $style): bool
    {
        return ($this->getCol() == $style->getCol() && $this->getRow() == $style->getRow());
    }
}