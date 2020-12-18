<?php

namespace Terminal\Style;

class BoldStyle extends Style
{
    public function __construct(int $row, int $col)
    {
        parent::__construct($row, $col);
    }
}