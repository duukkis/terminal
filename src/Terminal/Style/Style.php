<?php

namespace Terminal\Style;

class Style
{
    private int $row;
    private int $col;
    private int $screenNumber;

    public function __construct(int $row, int $col, int $screenNumber)
    {
        $this->row = $row;
        $this->col = $col;
        $this->screenNumber = $screenNumber;
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

    public function getScreenNumber(): int
    {
        return $this->screenNumber;
    }
}