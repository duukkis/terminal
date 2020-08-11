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
use Terminal\Commands\TabCommand;

class Interpret
{
    const BS = "\010";
    const NEWLINE = "\n";
    const CARRIAGE = "\r";
    const BRACKET = "[";

    const BACKSPACE = "[X";
    const LINEFEED = "[Y";
    const CARRIAGE_RETURN  = "[Z";

    const COLOR_FG_WHITE   = "[30m";
    const COLOR_FG_RED     = "[31m";
    const COLOR_FG_GREEN   = "[32m";
    const COLOR_FG_YELLOW  = "[33m";
    const COLOR_FG_BLUE    = "[34m";
    const COLOR_FG_MAGENTA = "[35m";
    const COLOR_FG_CYAN    = "[36m";
    const COLOR_FG_WHITE_2 = "[37m";

    const COLOR_BG_WHITE   = "[40m";
    const COLOR_BG_RED     = "[41m";
    const COLOR_BG_GREEN   = "[42m";
    const COLOR_BG_YELLOW  = "[43m";
    const COLOR_BG_BLUE    = "[44m";
    const COLOR_BG_MAGENTA = "[45m";
    const COLOR_BG_CYAN    = "[46m";
    const COLOR_BG_WHITE_2 = "[47m";

    const SET_CURSOR_KEY_TO_CURSOR = "[?1l";

    const CLEAR_SCREEN_DOWN = "[0J";
    const CLEAR_SCREEN_UP = "[1J";
    const CLEAR_SCREEN = "[2J";
    const TURN_OFF_CHARACTER_ATTRIBUTES = "[0m";
    const TURN_ON_BOLD = "[1m";
    const TURN_ON_LOW_INTENSITY = "[2m";
    const TURN_ON_UNDERLINE = "[4m";
    const TURN_ON_BLINK = "[5m";
    const TURN_ON_REVERSE_VIDEO = "[7m";
    const TURN_ON_INVISIBLE_TEXT = "[8m";

    const MOVE_ARROW_UP = "[A";
    const MOVE_ARROW_DOWN = "[B";
    const MOVE_ARROW_RIGHT = "[C";
    const MOVE_ARROW_LEFT = "[D";
    const CLEAR_SCREEN_DOWN_2 = "[J";
    const MOVE_CURSOR_HOME = "[H";
    const CLEAR_LINE_FROM_RIGHT = "[K";

    const CURSOR_MOVE_PREG = "/\[([0-9]+);([0-9]+)H/";
    const IGNORE = ['[?1049h', '[?1049l'];

    /**
     * interprets screen into individual commands
     * @param string $screen
     * @return Command[] array
     */
    public static function interpret(string $screen): array
    {
        $commands = [];
        $escape = chr(0x1B);

        // just remove terminal commands as they are present in screens without bringing any joy
        $screen = str_replace(self::IGNORE, '', $screen);

        // convert backspaces into ESC + [X chars and match em later
        $screen = str_replace(self::BS, $escape.self::BACKSPACE, $screen);
        // convert linefeeds into ESC + [Y chars and match em later
        $screen = str_replace(self::NEWLINE, $escape.self::LINEFEED, $screen);
        // convert carriage returns into ESC + [Z chars and match em later
        $screen = str_replace(self::CARRIAGE, $escape.self::CARRIAGE_RETURN, $screen);

        $pieces = explode($escape, $screen);
        foreach ($pieces as $piece) {
            $command = self::matchCommand($piece);
            while(null !== $command){
                $commands[] = $command;
                $command = self::matchCommand($command->getOutput());
                $piece = "";
            }
            if (strlen($piece) > 0) {
                $commands[] = new OutputCommand($piece);
            }
        }
        return $commands;
    }

    private static function matchCommand(string $command): ?Command
    {
        if (empty($command) || substr($command, 0, 1) !== self::BRACKET) {
           return null;
        }
        if (1 == preg_match(self::CURSOR_MOVE_PREG, $command, $matches)) {
            $output = substr($command, strlen($matches[0]));
            return new CursorMoveCommand((int) $matches[1], (int) $matches[2], $output);
        }
        $partFour = substr($command, 0, 4);
        $restFour = substr($command, 4);

        switch ($partFour) {
            case self::COLOR_FG_WHITE:
            case self::COLOR_FG_WHITE_2:
                return new ColorCommand(ColorCommand::WHITE, false, $restFour);
            case self::COLOR_FG_RED:
                return new ColorCommand(ColorCommand::RED, false, $restFour);
            case self::COLOR_FG_GREEN:
                return new ColorCommand(ColorCommand::GREEN, false, $restFour);
            case self::COLOR_FG_YELLOW:
                return new ColorCommand(ColorCommand::YELLOW, false, $restFour);
            case self::COLOR_FG_BLUE:
                return new ColorCommand(ColorCommand::BLUE, false, $restFour);
            case self::COLOR_FG_MAGENTA:
                return new ColorCommand(ColorCommand::MAGENTA, false, $restFour);
            case self::COLOR_FG_CYAN:
                return new ColorCommand(ColorCommand::CYAN, false, $restFour);
            case self::COLOR_BG_WHITE:
            case self::COLOR_BG_WHITE_2:
                return new ColorCommand(ColorCommand::WHITE, true, $restFour);
            case self::COLOR_BG_RED:
                return new ColorCommand(ColorCommand::RED, true, $restFour);
            case self::COLOR_BG_GREEN:
                return new ColorCommand(ColorCommand::GREEN, true, $restFour);
            case self::COLOR_BG_YELLOW:
                return new ColorCommand(ColorCommand::YELLOW, true, $restFour);
            case self::COLOR_BG_BLUE:
                return new ColorCommand(ColorCommand::BLUE, true, $restFour);
            case self::COLOR_BG_MAGENTA:
                return new ColorCommand(ColorCommand::MAGENTA, true, $restFour);
            case self::COLOR_BG_CYAN:
                return new ColorCommand(ColorCommand::CYAN, true, $restFour);

            case self::SET_CURSOR_KEY_TO_CURSOR:
                return new IgnoreCommand($restFour, "DECCKM - Set cursor key to cursor");
            default:
                break;
        }

        $partThree = substr($command, 0, 3);
        $restThree = substr($command, 3);

        switch ($partThree) {
            case self::CLEAR_SCREEN_DOWN:
                return new ClearScreenFromCursorCommand(true, false);
            case self::CLEAR_SCREEN_UP:
                return new ClearScreenFromCursorCommand(false, true);
            case self::CLEAR_SCREEN:
                return new ClearScreenCommand($restThree);
            case self::TURN_OFF_CHARACTER_ATTRIBUTES:
                return new IgnoreCommand($restThree, "SGR0 - Turn off character attributes");
            case self::TURN_ON_BOLD:
                return new IgnoreCommand($restThree, "SGR1 - Turn bold mode on");
            case self::TURN_ON_LOW_INTENSITY:
                return new IgnoreCommand($restThree, "SGR2 - Turn low intensity mode on");
            case self::TURN_ON_UNDERLINE:
                return new IgnoreCommand($restThree, "SGR4 - Turn underline mode on");
            case self::TURN_ON_BLINK:
                return new IgnoreCommand($restThree, "SGR5 - Turn blinking mode on");
            case self::TURN_ON_REVERSE_VIDEO:
                return new IgnoreCommand($restThree, "SGR7 - Turn reverse video on");
            case self::TURN_ON_INVISIBLE_TEXT:
                return new IgnoreCommand($restThree, "SGR8 - Turn invisible text mode on");
            default:
                break;
        }

        $partTwo = substr($command, 0, 2);
        $restTwo = substr($command, 2);

        switch ($partTwo){
            case self::MOVE_ARROW_UP:
                return new MoveArrowCommand(true, false, false, false, $restTwo);
            case self::MOVE_ARROW_DOWN:
                return new MoveArrowCommand(false, true, false, false, $restTwo);
            case self::MOVE_ARROW_RIGHT:
                return new MoveArrowCommand(false, false, true, false, $restTwo);
            case self::MOVE_ARROW_LEFT:
                return new MoveArrowCommand(false, false, false, true, $restTwo);
            case self::CLEAR_SCREEN_DOWN_2:
                return new ClearScreenFromCursorCommand(true, false);
            case self::MOVE_CURSOR_HOME:
                return new MoveCursorHomeCommand($restTwo);
            case self::CLEAR_LINE_FROM_RIGHT:
                return new ClearLineFromRightCommand($restTwo);
            // commands invented
            case self::BACKSPACE:
                return new BackspaceCommand($restTwo);
            case self::LINEFEED:
                return new NewlineCommand($restTwo);
            case self::CARRIAGE_RETURN:
                return new CarriageReturnCommand($restTwo);
            default:
                break;
        }

        return null;
    }

}