<?php

namespace Terminal;

class Console
{

    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
    }

    private array $rows = [];

    public function copy(): Console
    {
        $newRows = [];
        /** @var ConsoleRow $row */
        foreach ($this->rows as $index => $row) {
            $newRows[$index] = $row->copy();
        }
        return new Console($newRows);
    }

    public function setRow(int $row, ConsoleRow $consoleRow): ConsoleRow
    {
        if ($consoleRow->isEmpty()) {
            unset($this->rows[$row]);
        } else {
            $this->rows[$row] = $consoleRow;
        }
        return $consoleRow;
    }

    public function getRow(int $row): ConsoleRow
    {
        return (isset($this->rows[$row])) ? $this->rows[$row] : new ConsoleRow("");
    }

    public function getLastLine(): int
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
