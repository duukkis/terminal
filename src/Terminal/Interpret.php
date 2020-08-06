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
use Terminal\Commands\TabCommand;

class Interpret
{

    public static function interpret(string $screen): array
    {
        $commands = [];

        // just remove terminal commands as they are present in screens without bringing any joy
        $screen = str_replace(['[?1049h', '[?1049l'], '', $screen);

        $escape = chr(0x1B);
        $pieces = explode($escape, $screen);
        foreach ($pieces as $piece) {
            $c = self::matchCommand($piece);
            if (null !== $c) {
                $commands[] = $c;
                $additionalCommand = self::matchCommand($c->getOutput());
                while(null !== $additionalCommand) {
                    $commands[] = $additionalCommand;
                    $additionalCommand = self::matchCommand($additionalCommand->getOutput());
                }
                $piece = "";
            }
            if (strlen($piece) > 0) {
                $commands[] = new OutputCommand($piece);
            }
        }
        return $commands;
    }

    private static function matchCommand($command): ?Command
    {
        if (empty($command)) {
           return null;
        }
        $firstTwoOfCommand = substr($command, 0, 2);
        $lastFromTwoOfCommand = substr($command, 2);
        $firstThreeOfCommand = substr($command, 0, 3);
        $lastFromThreeOfCommand = substr($command, 3);
        $firstFourOfCommand = substr($command, 0, 4);
        $lastFromFourOfCommand = substr($command, 4);

        if ("[H" == $firstTwoOfCommand) { return new MoveCursorHomeCommand($lastFromTwoOfCommand); }
        if ("[K" == $firstTwoOfCommand) { return new ClearLineFromRightCommand($lastFromTwoOfCommand); }
        if ("[7m" == $firstThreeOfCommand) { return new ReverseVideoCommand($lastFromThreeOfCommand); }
        if ("[A" == $firstTwoOfCommand) { return new MoveArrowCommand(true, false, false, false, $lastFromTwoOfCommand); }
        if ("[B" == $firstTwoOfCommand) { return new MoveArrowCommand(false, true, false, false, $lastFromTwoOfCommand); }
        if ("[C" == $firstTwoOfCommand) { return new MoveArrowCommand(false, false, true, false, $lastFromTwoOfCommand); }
        if ("[D" == $firstTwoOfCommand) { return new MoveArrowCommand(false, false, false, true, $lastFromTwoOfCommand); }
        if ("[J" == $command) { return new ClearScreenFromCursorCommand(true, false); }
        if ("[0J" == $command) { return new ClearScreenFromCursorCommand(true, false); }
        if ("[1J" == $command) { return new ClearScreenFromCursorCommand(false, true); }
        if ("[2J" == $firstThreeOfCommand) { return new ClearScreenCommand($lastFromThreeOfCommand); }
        if (1 == preg_match("/\[([0-9]+);([0-9]+)H/", $command, $matches)) {
            $output = substr($command, strlen($matches[0]));
            return new CursorMoveCommand((int) $matches[1], (int) $matches[2], $output);
        }
        if ("[30m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::WHITE, false, $lastFromFourOfCommand); }
        if ("[31m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::RED, false, $lastFromFourOfCommand); }
        if ("[32m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::GREEN, false, $lastFromFourOfCommand); }
        if ("[33m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::YELLOW, false, $lastFromFourOfCommand); }
        if ("[34m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::BLUE, false, $lastFromFourOfCommand); }
        if ("[35m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::MAGENTA, false, $lastFromFourOfCommand); }
        if ("[36m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::CYAN, false, $lastFromFourOfCommand); }
        if ("[37m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::WHITE, false, $lastFromFourOfCommand); }
        if ("[40m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::WHITE, true, $lastFromFourOfCommand); }
        if ("[41m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::RED, true, $lastFromFourOfCommand); }
        if ("[42m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::GREEN, true, $lastFromFourOfCommand); }
        if ("[43m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::YELLOW, true, $lastFromFourOfCommand); }
        if ("[44m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::BLUE, true, $lastFromFourOfCommand); }
        if ("[45m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::MAGENTA, true, $lastFromFourOfCommand); }
        if ("[46m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::CYAN, true, $lastFromFourOfCommand); }
        if ("[47m" == $firstFourOfCommand) { return new ColorCommand(ColorCommand::WHITE, true, $lastFromFourOfCommand); }

        if ("[?1l" == $command) { return new IgnoreCommand("", "DECCKM - Set cursor key to cursor"); }
        if ("[0m" == $firstThreeOfCommand) { return new IgnoreCommand($lastFromThreeOfCommand, "SGR0 - Turn off character attributes"); }
        if ("[1m" == $command) { return new IgnoreCommand("", "SGR1 - Turn bold mode on"); }
        if ("[2m" == $command) { return new IgnoreCommand("", "SGR2 - Turn low intensity mode on"); }
        if ("[4m" == $command) { return new IgnoreCommand("", "SGR4 - Turn underline mode on"); }
        if ("[5m" == $command) { return new IgnoreCommand("", "SGR5 - Turn blinking mode on"); }
        if ("[7m" == $command) { return new IgnoreCommand("", "SGR7 - Turn reverse video on"); }
        if ("[8m" == $command) { return new IgnoreCommand("", "SGR8 - Turn invisible text mode on"); }
        return null;
    }

}