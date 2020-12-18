<?php

namespace Terminal;

class Console
{

    private array $console = [];

    public function setRow(int $row, ConsoleRow $consoleRow): ConsoleRow
    {
        if ($consoleRow->isEmpty()) {
            unset($this->console[$row]);
        } else {
            $this->console[$row] = $consoleRow;
        }
        return $consoleRow;
    }

    public function isRowSet(int $row): bool
    {
        return (isset($this->console[$row])) ? true : false;
    }

    public function getRow(int $row): ?ConsoleRow
    {
        return (isset($this->console[$row])) ? $this->console[$row] : null;
    }

    public function getMaxIndex(): int
    {
        return (!empty($this->console)) ? max(array_keys($this->console)) : 0;
    }


    /**
     * removes rows from here to below
     * @param int $row
     */
    public function clearRowsDownFrom(int $row): void
    {
        foreach ($this->console as $rowindex => $dada) {
            if ($rowindex >= $row) {
                unset($this->console[$rowindex]);
            }
        }
    }

    /**
     * Removes rows from here to up
     * @param int $row
     */
    public function clearRowsUpFrom(int $row): void
    {
        foreach ($this->console as $rowindex => $dada) {
            if ($rowindex <= $row) {
                unset($this->console[$rowindex]);
            }
        }
    }
}