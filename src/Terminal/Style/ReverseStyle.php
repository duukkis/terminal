<?php

namespace Terminal\Style;

class ReverseStyle extends Style
{
    public function __construct(int $row, int $col)
    {
        parent::__construct($row, $col);
    }
}