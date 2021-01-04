<?php

namespace Terminal;

use Exception;
use Terminal\Commands\AddStyleCommand;
use Terminal\Commands\BackspaceCommand;
use Terminal\Commands\CarriageReturnCommand;
use Terminal\Commands\ClearLineCommand;
use Terminal\Commands\ClearScreenCommand;
use Terminal\Commands\ClearScreenFromCursorCommand;
use Terminal\Commands\ColorCommand;
use Terminal\Commands\ColorCommand256;
use Terminal\Commands\Command;
use Terminal\Commands\CursorMoveCommand;
use Terminal\Commands\EraseCharactersCommand;
use Terminal\Commands\IgnoreCommand;
use Terminal\Commands\MoveArrowCommand;
use Terminal\Commands\MoveCursorHomeCommand;
use Terminal\Commands\NewlineCommand;
use Terminal\Commands\OutputCommand;
use Terminal\Commands\RemoveStyleCommand;
use Terminal\Style\BoldStyle;
use Terminal\Style\ClearStyle;
use Terminal\Style\ColorStyle;
use Terminal\Style\ReverseStyle;
use Terminal\Style\Style;
use Terminal\Style\UnderlineStyle;

class Terminal {

    const COLUMN_BEGINNING = 1;
    const ROW_BEGINNING = 1;
    private array $screens = [];
    // parsing related variables
    const TAB = "\t";
    // console array with TerminalRows / row index
    private Console $console;
    // current cursor position
    private int $cursorRow = 0;
    private int $cursorCol = 0;
    private int $tabWidth = 8;
    private string $tabString;

    public function __construct(?string $file = null)
    {
        if (null !== $file) {
            $data = @file_get_contents($file);
            if (!empty($data)) {
                $this->parseScreens($data);
            }
        }
        // set tab on construct
        $this->console = new Console();
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
                $timeoutInMicros = self::calculateDiffBetweenScreens($screen, $prevScreen);
            }
            usleep($timeoutInMicros);
            print ($screen->screen);
            if ($actual) {
                $prevScreen = $screen;
            }
        }
    }

    public static function calculateDiffBetweenScreens(Screen $screen, Screen $previousScreen): int
    {
        return (1000000 * ($screen->sec - $previousScreen->sec)) + ($screen->usec - $previousScreen->usec);
    }

    private function clearConsole(): void
    {
        $this->console = new Console();
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
     * @return Console
     */
    public function gotoScreen(int $screenNumber): Console
    {
        $this->loopScreens(false, false, $screenNumber);
        return $this->getConsole();
    }

    /**
     * get console
     * @return Console
     */
    public function getConsole(): Console
    {
        return $this->console;
    }

    public function getDurationInMicroseconds(): int
    {
        if (empty($this->screens)) {
            return 0;
        }
        $firstScreen = $this->screens[0];
        $lastScreen = $this->screens[count($this->screens) - 1];
        return (1000000 * ($lastScreen->sec - $firstScreen->sec)) + ($lastScreen->usec - $firstScreen->usec);
    }

    public function getDurationInSeconds(): int
    {
        return ceil($this->getDurationInMicroseconds() / 1000000);
    }

    /**
     * loop screens and define maxes of screens
     * @param bool $writeScreensIntoFiles
     * @param bool $commandsToDebug
     * @param int|null $stopAtScreen
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
                    case EraseCharactersCommand::class:
                        $this->parseOutputToTerminal($command->output, $screenNumber);
                        break;
                    case NewlineCommand::class:
                        $this->cursorCol = self::COLUMN_BEGINNING;
                        $this->cursorRow++;
                        $this->parseOutputToTerminal($command->getOutput(), $screenNumber);
                        break;
                    case CarriageReturnCommand::class:
                        $this->cursorCol = self::COLUMN_BEGINNING;
                        $this->parseOutputToTerminal($command->getOutput(), $screenNumber);
                        break;
                    case CursorMoveCommand::class:
                        $this->cursorRow = $command->row ?? $this->cursorRow;
                        $this->cursorCol = $command->col ?? $this->cursorCol;
                        $this->parseOutputToTerminal($command->getOutput(), $screenNumber);
                        break;
                    case ClearLineCommand::class:
                        $this->clearLine($command->right, $command->left);
                        $this->parseOutputToTerminal($command->getOutput(), $screenNumber);
                        break;
                    case MoveArrowCommand::class:
                        if ($command->up) { $this->cursorRow--; }
                        if ($command->down) { $this->cursorRow++; }
                        if ($command->right) { $this->cursorCol++; }
                        if ($command->left) { $this->cursorCol--; }
                        $this->parseOutputToTerminal($command->getOutput(), $screenNumber);
                        break;
                    case OutputCommand::class:
                    case IgnoreCommand::class:
                        $this->parseOutputToTerminal($command->getOutput(), $screenNumber);
                        break;
                    case MoveCursorHomeCommand::class:
                        $this->cursorRow = self::ROW_BEGINNING;
                        $this->cursorCol = self::COLUMN_BEGINNING;
                        $this->parseOutputToTerminal($command->getOutput(), $screenNumber);
                        break;
                    case ColorCommand::class:
                    case ColorCommand256::class:
                        $this->addStyleToConsoleRow($this->getStyle($command, $screenNumber));
                        $this->parseOutputToTerminal($command->getOutput(), $screenNumber);
                        break;
                    case ClearScreenFromCursorCommand::class:
                        if ($command->down) {
                            $this->console->clearRowsDownFrom($this->cursorRow);
                        }
                        if ($command->up) {
                            $this->console->clearRowsUpFrom($this->cursorRow);
                        }
                        break;
                    case AddStyleCommand::class:
                        $style = $this->getStyle($command, $screenNumber);
                        if ($style !== null) {
                            $this->addStyleToConsoleRow($style);
                        }
                        $this->parseOutputToTerminal($command->getOutput(), $screenNumber);
                        break;
                    case RemoveStyleCommand::class:
                        $this->addStyleToConsoleRow(
                            new ClearStyle($this->cursorRow, $this->cursorCol, $screenNumber)
                        );
                        $this->parseOutputToTerminal($command->getOutput(), $screenNumber);
                        break;
                    default:
                        print $commClass;
                        print_r($command);
                        die("Not implemented");
                }
            }
            // set the print out into Screen so it's reusable
            $this->screens[$screenNumber]->setConsole($this->console->copy());
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
     * @param Command $styleCommand
     * @return Style
     */
    private function getStyle(Command $styleCommand, int $screenNumber): ?Style
    {
        if (get_class($styleCommand) === AddStyleCommand::class) {
            /** @var $styleCommand AddStyleCommand */
            if ($styleCommand->getStyle() === AddStyleCommand::BOLD) {
                return new BoldStyle($this->cursorRow, $this->cursorCol, $screenNumber);
            } elseif ($styleCommand->getStyle() === AddStyleCommand::UNDERLINE) {
                return new UnderlineStyle($this->cursorRow, $this->cursorCol, $screenNumber);
            } elseif ($styleCommand->getStyle() === AddStyleCommand::REVERSE) {
                return new ReverseStyle($this->cursorRow, $this->cursorCol, $screenNumber);
            }
        } elseif (get_class($styleCommand) === ColorCommand::class) {
            /** @var $styleCommand ColorCommand */
            $colors = $styleCommand->colorToRGB();
            return new ColorStyle(
                $this->cursorRow,
                $this->cursorCol,
                $screenNumber,
                $colors["r"],
                $colors["g"],
                $colors["b"],
                $styleCommand->isBackground()
            );
        } elseif (get_class($styleCommand) === ColorCommand256::class) {
            /** @var $styleCommand ColorCommand256 */
            $colors = $styleCommand->colorToRGB();
            return new ColorStyle(
                $this->cursorRow,
                $this->cursorCol,
                $screenNumber,
                $colors["r"],
                $colors["g"],
                $colors["b"],
                $styleCommand->isBackground()
            );
        }
        // invisible and blink will return null
        return null;
    }

    private function clearLine(bool $clearLineFromRight = false, bool $clearLineFromLeft = false) {
        if ($clearLineFromRight && $clearLineFromLeft) {
            $this->console->setRow($this->cursorRow, new ConsoleRow(""));
            return;
        }
        $existingRow = $this->console->getRow($this->cursorRow);

        // if clearLineFromRight
        if ($clearLineFromRight && $existingRow != null) {
            $existingRow->setOutputTo($this->cursorCol);
            $existingRow->setStylesBefore($this->cursorCol);
            $this->console->setRow($this->cursorRow, $existingRow);
        }
        // if clearLineFromLeft
        else if ($clearLineFromLeft && $existingRow != null) {
            $newOutput = str_pad("", $this->cursorCol, " ") . $existingRow->getOutputFrom($this->cursorCol);
            $existingRow->output = $newOutput;
            $existingRow->setStylesAfter($this->cursorCol);
            $this->console->setRow($this->cursorRow, $existingRow);
        }
    }

    private function addStyleToConsoleRow(Style $style)
    {
        $consoleRow = $this->console->getRow($this->cursorRow) ?? new ConsoleRow("");
        $consoleRow->addStyle($this->cursorCol, $style);
        $this->console->setRow($this->cursorRow, $consoleRow);
    }

    /**
     * Parses output to a console
     * @param string $output
     */
    private function parseOutputToTerminal(string $output, int $screenNumber)
    {
        if (strlen($output) == 0) {
            return;
        }
        $consoleRow = $this->console->getRow($this->cursorRow);
        // replace tabs with spaces
        $output = str_replace(self::TAB, $this->tabString, $output);

        $outputLen = strlen($output);
        // if there is existing items in row, get the contents
        // and prepend and append it to new output based on cursorCol
        if ($consoleRow !== null) {
            // we need to add all before styles and after styles and also keep the screenNumber styles
            // |----- keep all styles ---- $this->cursorCol | --- keep $screenNumber styles --- | $this->cursorCol +
            // $outputLen --- keep all
            $consoleRow->setBeforeAfterStyles($this->cursorCol, $this->cursorCol + $outputLen, $screenNumber);

            $beginningOutputFromExisting = $consoleRow->getOutputTo($this->cursorCol);
            $endOutputFromExisting = $consoleRow->getOutputFrom($this->cursorCol + $outputLen);
            $output = str_pad($beginningOutputFromExisting, $this->cursorCol, " ", STR_PAD_RIGHT).$output.$endOutputFromExisting;
            $consoleRow->output = $output;
        } else {
            $output = str_pad($output, ($this->cursorCol + $outputLen), " ", STR_PAD_LEFT);
            $consoleRow = new ConsoleRow($output);
        }

        $this->cursorCol += $outputLen;
        $this->console->setRow($this->cursorRow, $consoleRow);
    }

    /**
     * Debugger that writes items into temp files with commands
     * @param int $index
     * @param array $commands
     * @param bool $commandsToDebug
     */
    private function linesToFiles(int $index, array $commands, bool $commandsToDebug){
      $lastLine = $this->console->getLastLine();
      $data = '';
      $styles = '';
      for ($i = 0;$i <= $lastLine;$i++) {
        $row = $this->console->getRow($i);
        if ($row !== null) {
          $data .= $row->output;
          $s = $row->getStyles();
          if (!empty($s)) {
              $styles .= "ROW ".$i . PHP_EOL . print_r($s, true);
              $styles .= PHP_EOL . print_r($row->getStyleLengths(), true);
          }
        }
        $data .= PHP_EOL;
      }
      if ($commandsToDebug) {
          $data .= print_r($commands, true);
          $data .= $styles;
      }
      try{
          file_put_contents(__DIR__."/../../temp/screen_".$index.".txt", $data);
      } catch (Exception $e) {
          print ($e->getMessage());
          print ($e->getTraceAsString());
          die();
      }
    }
}
