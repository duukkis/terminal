<?php

namespace Terminal;

use Terminal\Commands\ClearLineFromRightCommand;
use Terminal\Commands\ClearScreenCommand;
use Terminal\Commands\ClearScreenFromCursorCommand;
use Terminal\Commands\ColorCommand;
use Terminal\Commands\Command;
use Terminal\Commands\CursorMoveCommand;
use Terminal\Commands\IgnoreCommand;
use Terminal\Commands\MoveArrowCommand;
use Terminal\Commands\MoveCursorHomeCommand;
use Terminal\Commands\OutputCommand;
use Terminal\Commands\ReverseVideoCommand;

class Terminal {

    private array $screens = [];
    // parsing related variables
    const NEWLINE = "\n";
    const TAB = "\t";
    // console array with TerminalRows / row index
    private array $console = [];
    // current cursor position
    private int $cursorRow = 0;
    private int $cursorCol = 0;
    private int $tabWidth = 8;
    private string $tabString = "";

    public function __construct(?string $file = null)
    {
        if (null !== $file) {
            $data = @file_get_contents($file);
            if (!empty($data)) {
                $this->parseScreens($data);
            }
        }
        $this->setTab();
    }

    /**
     * Parse the screens from ttyrec file contents
     * @param string $contents
     */
    private function parseScreens(string $contents)
    {
        while(strlen($contents) > 0) {
            $data = unpack('Vsec/Vusec/Vlen', $contents);
            $contents = substr($contents, 12);
            $screen = substr($contents, 0, $data["len"]);
            $this->setScreen((int) $data["sec"], (int) $data["usec"], (int) $data["len"], $screen);
            $contents = substr($contents, $data["len"]);
        }
    }

    private function setScreen(int $sec, int $usec, int $len, string $screen)
    {
        $this->screens[] = new Screen($sec, $usec, $len, $screen);
    }

    public function getScreens()
    {
        return $this->screens;
    }

    /**
     * @param bool $actual - run with actual timestamps
     * @param int $timeoutInMicros - constant delay between frames quarter of a second
     */
    public function printScreens(bool $actual = false, int $timeoutInMicros = 250000)
    {
        $prevScreen = null;
        /** @var Screen $screen */
        foreach ($this->screens as $screen) {
            if ($actual && null !== $prevScreen) {
                $timeoutInMicros = $this->calculateDiffBetweenScreens($screen, $prevScreen);
            }
            usleep($timeoutInMicros);
            print ($screen->screen);
            if ($actual) {
                $prevScreen = $screen;
            }
        }
    }

    public function calculateDiffBetweenScreens(Screen $screen, Screen $previousScreen)
    {
        return (1000000 * ($screen->sec - $previousScreen->sec)) + ($screen->usec - $previousScreen->usec);
    }


    private function setTab()
    {
        $this->tabString = str_pad("", $this->tabWidth, " ");
    }

    private function clearConsole()
    {
        $this->console = [];
    }

    /**
     * loop screens and define maxes of screens
     */
    public function loopScreens($debug = false) {
        /** @var Screen $screen */
        foreach ($this->screens as $screenNumber => $screen) {
            $commands = $screen->getCommands();
            /** @var Command $command */
            foreach ($commands as $command) {
                $commClass = get_class($command);
                switch($commClass)
                {
                    case ClearScreenCommand::class:
                        $this->clearConsole();
                        break;
                    case CursorMoveCommand::class:
                        $this->cursorRow = $command->row;
                        $this->cursorCol = $command->col;
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case ClearLineFromRightCommand::class:
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case MoveArrowCommand::class:
                        if ($command->up) { $this->cursorRow--; }
                        if ($command->down) { $this->cursorRow++; }
                        if ($command->right) { $this->cursorCol++; }
                        if ($command->left) { $this->cursorCol--; }
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case OutputCommand::class:
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case MoveCursorHomeCommand::class:
                        $this->cursorRow = 0;
                        $this->cursorCol = 0;
                        $this->parseOutputToTerminal($command->getOutput());
                    case ColorCommand::class:
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case ReverseVideoCommand::class:
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case ClearScreenFromCursorCommand::class:
                        if ($command->down) {
                            $this->clearRowsDownFrom($this->cursorRow);
                        }
                        if ($command->up) {
                            $this->clearRowsUpFrom($this->cursorRow);
                        }
                    case IgnoreCommand::class:
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    default:
                        print $commClass;
                        print_r($command);
                        die("Not implemented yet");
                }
            }
            if ($debug) {
              // write consoles into temp files with commands
              $this->linesToFiles($screenNumber, $commands);
            }
        }
    }

    /**
     * removes rows from here to below
     * @param $row
     */
    private function clearRowsDownFrom($row)
    {
        foreach ($this->console as $rowindex => $dada) {
            if ($rowindex >= $row) {
                unset($this->console[$rowindex]);
            }
        }
    }

    /**
     * Removes rows from here to up
     * @param $row
     */
    private function clearRowsUpFrom($row)
    {
        foreach ($this->console as $rowindex => $dada) {
            if ($rowindex <= $row) {
                unset($this->console[$rowindex]);
            }
        }
    }

    /**
     * Parses output to a console
     * @param $output
     * @param $row
     * @param $col
     */
    private function parseOutputToTerminal($output)
    {
        if (empty($output)) {
          return;
        }
        $linesToParse = [];
        $rowsFromOutput = explode(self::NEWLINE, $output);
      
        foreach ($rowsFromOutput as $trash => $item) {
              // remove control M's
              $item = str_replace("\x0D", "", $item);
              // replace tabs with spaces
              $item = str_replace(self::TAB, $this->tabString, $item);
          
              $itemLen = strlen($item);
              // if there is existing items in row, get the contents pre cursorCol
              // and prepend it to new output
              if (isset($this->console[$this->cursorRow])) {
                  $existingRow = $this->console[$this->cursorRow];
                  $leaveThisOutputFromExisting = $existingRow->getOutputTo($this->cursorCol);
                  $item = str_pad($leaveThisOutputFromExisting, $this->cursorCol, " ", STR_PAD_RIGHT).$item;
              } else {
                  $item = str_pad($item, ($this->cursorCol + $itemLen), " ", STR_PAD_LEFT);
              }
              $this->cursorCol += $itemLen;
              $this->console[$this->cursorRow] = new TerminalRow($item);
        }
    }

    /**
     * Debugger that writes items into temp files with commands
     */
    public function linesToFiles($index, $commands){
      $lastLine = 0;
      if (!empty($this->console)) {
        $lastLine = max(array_keys($this->console));
      }
      $data = '';
      for ($i = 0;$i <= $lastLine;$i++) {
        if (isset($this->console[$i])) {
          $data .= $this->console[$i]->output;
        }
        $data .= PHP_EOL;
      }
      $data .= print_r($commands, true);
      file_put_contents(__DIR__."/../../temp/screen_".$index.".txt", $data);
    }
}
