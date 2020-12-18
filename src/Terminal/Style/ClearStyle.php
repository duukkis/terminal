<?php

namespace Terminal\Style;

class ClearStyle extends Style
{
    public function __construct(int $row, int $col)
    {
        parent::__construct($row, $col);
    }
}