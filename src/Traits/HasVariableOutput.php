<?php

namespace ReliQArts\Docweaver\Traits;

use App;
use Illuminate\Console\Command;

/**
 * HasVariableOutput trait.
 */
trait HasVariableOutput
{
    /**
     * Calling command if running in console.
     *
     * @var Command
     */
    protected $callingCommand;

    /**
     * Convert <br/> to newlines.
     *
     * @param string $text
     *
     * @return string
     */
    protected function br2nl($text)
    {
        return preg_replace('/<br[\\/]?>/', "\n", $text);
    }

    /**
     * Print to console or screen.
     *
     * @param string $text
     * @param string $direction in|out
     *
     * @return string
     */
    protected function tell($text, $direction = 'out')
    {
        $direction = strtolower($direction);
        $nl = App::runningInConsole() ? "\n" : '<br/>';
        $dirSymbol = ($direction === 'in' ? '>> ' : ($direction === 'flat' ? '-- ' : '<< '));
        if ($direction === 'none') {
            $dirSymbol = '';
        }

        if (App::runningInConsole() && $this->callingCommand) {
            if ($direction === 'out') {
                $this->callingCommand->line("<info>\\<\\< {$text}</info>");
            } else {
                $this->callingCommand->line("{$dirSymbol}{$text}");
            }
        } else {
            echo "{$nl}{$dirSymbol}{$text}";
        }

        return $text;
    }
}
