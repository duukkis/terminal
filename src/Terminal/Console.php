<?php

namespace Terminal;

class Console
{

    private array $rows = [];

    public function setRow(int $row, ConsoleRow $consoleRow): ConsoleRow
    {
        if ($consoleRow->isEmpty()) {
            unset($this->rows[$row]);
        } else {
            $this->rows[$row] = $consoleRow;
        }
        return $consoleRow;
    }

    public function isRowSet(int $row): bool
    {
        return (isset($this->rows[$row])) ? true : false;
    }

    public function getRow(int $row): ?ConsoleRow
    {
        return (isset($this->rows[$row])) ? $this->rows[$row] : null;
    }

    public function getMaxIndex(): int
    {
        return (!empty($this->rows)) ? max(array_keys($this->rows)) : 0;
    }


    /**
     * removes rows from here to below
     * @param int $row
     */
    public function clearRowsDownFrom(int $row): void
    {
        foreach ($this->rows as $rowindex => $dada) {
            if ($rowindex >= $row) {
                unset($this->rows[$rowindex]);
            }
        }
    }

    /**
     * Removes rows from here to up
     * @param int $row
     */
    public function clearRowsUpFrom(int $row): void
    {
        foreach ($this->rows as $rowindex => $dada) {
            if ($rowindex <= $row) {
                unset($this->rows[$rowindex]);
            }
        }
    }
}