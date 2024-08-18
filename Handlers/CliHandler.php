<?php

/**
 * @Package:    MaplePHP - Error Plain text handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\SeverityLevelPool;
use MaplePHP\Prompts\Ansi;
use Throwable;

class CliHandler extends TextHandler
{
    /**
     * Exception handler output
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $this->getHttp()->response()->getBody()->write($this->getErrorMessage($exception));
        $this->emitter($exception);
    }

    /**
     * Generate error message
     * @param Throwable $exception
     * @return string
     */
    protected function getErrorMessage(Throwable $exception): string
    {

        $ansi = new Ansi();
        $traceLine = $ansi->bold("#%s ") . "%s(" . $ansi->bold("%s") . "): %s(%s)";

        $msg = "\n";
        $msg .= $ansi->blue("%s ") . $ansi->italic("(%s)") . ": ";
        $msg .= $ansi->bold("%s ") . " \n\n";
        $msg .= $ansi->bold("File: ") . "%s:(" . $ansi->bold("%s") . ")\n\n";
        //$msg .= $ansi->bold("Stack trace:") . "\n";
        //$msg .= "%s\n";
        //$msg .= "thrown in %s on <strong>line %s</strong>";

        $key = 0;
        $result = array();
        $trace = $this->getTrace($exception);
        $severityLevel = (method_exists($exception, "getSeverity") ? $exception->getSeverity() : 0);

        foreach ($trace as $key => $stackPoint) {
            $result[] = sprintf(
                $traceLine,
                $key,
                ($stackPoint['file'] ?? 0),
                ($stackPoint['line'] ?? 0),
                ($stackPoint['function'] ?? "void"),
                implode(', ', $stackPoint['args'])
            );
        }

        // trace always ends with {main}
        //$result[] = '#' . ($key+1) . ' {main}';

        // write trace-lines into main template
        return sprintf(
            $msg,
            get_class($exception),
            SeverityLevelPool::getSeverityLevel($severityLevel, "Error"),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode("\n", $result),
            $exception->getFile(),
            $exception->getLine()
        )."\n";
    }

    /**
     * This is the visible code block
     * @param array $data
     * @param string $code
     * @param int $index
     * @return string
     */
    protected function getCodeBlock(array $data, string $code, int $index = 0): string
    {
        return $code;
    }
}
