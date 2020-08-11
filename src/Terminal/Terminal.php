<?php

namespace Terminal;

use Terminal\Commands\BackspaceCommand;
use Terminal\Commands\CarriageReturnCommand;
use Terminal\Commands\ClearLineFromRightCommand;
use Terminal\Commands\ClearScreenCommand;
use Terminal\Commands\ClearScreenFromCursorCommand;
use Terminal\Commands\ColorCommand;
use Terminal\Commands\Command;
use Terminal\Commands\CursorMoveCommand;
use Terminal\Commands\IgnoreCommand;
use Terminal\Commands\MoveArrowCommand;
use Terminal\Commands\MoveCursorHomeCommand;
use Terminal\Commands\NewlineCommand;
use Terminal\Commands\OutputCommand;

class Terminal {

    const COLUMN_BEGINNING = 1;
    const ROW_BEGINNING = 1;
    private array $screens = [];
    // parsing related variables
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
        // set tab on construct
        $this->tabString = str_pad("", $this->tabWidth, " ");
    }

    /**
     * Parse the screens from ttyrec file contents
     * @param string $contents
     */
    private function parseScreens(string $contents)
    {
        while(strlen($contents) > 0) {
            $data = unpack('Vsec/Vusec/Vlen', $contents);
            $len = (int) $data["len"];
            $contents = substr($contents, 12);
            $screen = substr($contents, 0, $len);
            $this->setScreen((int) $data["sec"], (int) $data["usec"], $len, $screen);
            $contents = substr($contents, $len);
        }
    }

    private function setScreen(int $sec, int $usec, int $len, string $screen): void
    {
        $this->screens[] = new Screen($sec, $usec, $len, $screen);
    }

    public function getScreens(): array
    {
        return $this->screens;
    }

    /**
     * @param bool $actual - run with actual timestamps
     * @param int $timeoutInMicros - constant delay between frames quarter of a second
     */
    public function printScreens(bool $actual = false, int $timeoutInMicros = 250000): void
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

    public function calculateDiffBetweenScreens(Screen $screen, Screen $previousScreen): int
    {
        return (1000000 * ($screen->sec - $previousScreen->sec)) + ($screen->usec - $previousScreen->usec);
    }

    private function clearConsole(): void
    {
        $this->console = [];
    }

    /**
     * return number of screens
     * @return int
     */
    public function numberOfScreens(): int
    {
        return count($this->screens);
    }

    /**
     * goto screen and stop there, returns the console
     * @param int $screenNumber
     * @return array
     */
    public function gotoScreen(int $screenNumber): array
    {
        $this->loopScreens(false, false, $screenNumber);
        return $this->getConsole();
    }

    /**
     * get console
     * @return array
     */
    public function getConsole(): array
    {
        return $this->console;
    }

    /**
     * loop screens and define maxes of screens
     */
    public function loopScreens(
        bool $writeScreensIntoFiles = false,
        bool $commandsToDebug = false,
        ?int $stopAtScreen = null
    ) {
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
                    case BackspaceCommand::class:
                        $this->cursorCol--;
                        break;
                    case NewlineCommand::class:
                        $this->cursorCol = self::COLUMN_BEGINNING;
                        $this->cursorRow++;
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case CarriageReturnCommand::class:
                        $this->cursorCol = self::COLUMN_BEGINNING;
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case CursorMoveCommand::class:
                        $this->cursorRow = $command->row;
                        $this->cursorCol = $command->col;
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case ClearLineFromRightCommand::class:
                        $this->parseOutputToTerminal($command->getOutput(), true);
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
                        $this->cursorRow = self::ROW_BEGINNING;
                        $this->cursorCol = self::COLUMN_BEGINNING;
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case ColorCommand::class:
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    case ClearScreenFromCursorCommand::class:
                        if ($command->down) {
                            $this->clearRowsDownFrom($this->cursorRow);
                        }
                        if ($command->up) {
                            $this->clearRowsUpFrom($this->cursorRow);
                        }
                        break;
                    case IgnoreCommand::class:
                        $this->parseOutputToTerminal($command->getOutput());
                        break;
                    default:
                        print $commClass;
                        print_r($command);
                        die("Not implemented yet");
                }
            }
            if (null !== $stopAtScreen && $screenNumber == $stopAtScreen) {
                break;
            }
            if ($writeScreensIntoFiles) {
              // write consoles into temp files with commands
              $this->linesToFiles($screenNumber, $commands, $commandsToDebug);
            }
        }
    }

    /**
     * removes rows from here to below
     * @param int $row
     */
    private function clearRowsDownFrom(int $row)
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
    private function clearRowsUpFrom(int $row)
    {
        foreach ($this->console as $rowindex => $dada) {
            if ($rowindex <= $row) {
                unset($this->console[$rowindex]);
            }
        }
    }

    /**
     * Parses output to a console
     * @param string $output
     * @param bool $clearFromRight
     */
    private function parseOutputToTerminal(string $output, bool $clearLineFromRight = false)
    {
        // if clearLineFromRight
        if ($clearLineFromRight && isset($this->console[$this->cursorRow])) {
            $existingRow = $this->console[$this->cursorRow];
            $this->console[$this->cursorRow] = new TerminalRow($existingRow->getOutputTo($this->cursorCol));
        }
        if (strlen($output) == 0) {
          return;
        }
        // replace tabs with spaces
        $output = str_replace(self::TAB, $this->tabString, $output);

        $outputLen = strlen($output);
        // if there is existing items in row, get the contents
        // and prepend and append it to new output based on cursorCol
        if (isset($this->console[$this->cursorRow])) {
            $existingRow = $this->console[$this->cursorRow];
            $beginningOutputFromExisting = $existingRow->getOutputTo($this->cursorCol);
            $endOutputFromExisting = $existingRow->getOutputFrom($this->cursorCol + $outputLen);
            $output = str_pad($beginningOutputFromExisting, $this->cursorCol, " ", STR_PAD_RIGHT).$output.$endOutputFromExisting;
        } else {
            $output = str_pad($output, ($this->cursorCol + $outputLen), " ", STR_PAD_LEFT);
        }
        $this->cursorCol += $outputLen;
        $this->console[$this->cursorRow] = new TerminalRow($output);
    }

    /**
     * Debugger that writes items into temp files with commands
     * @param int $index
     * @param array $commands
     */
    private function linesToFiles(int $index, array $commands, bool $commandsToDebug){
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
      if ($commandsToDebug) {
          $data .= print_r($commands, true);
      }
      try{
          file_put_contents(__DIR__."/../../temp/screen_".$index.".txt", $data);
      } catch (\Exception $e) {
          print ($e->getMessage());
          print ($e->getTraceAsString());
          die();
      }
    }
}
