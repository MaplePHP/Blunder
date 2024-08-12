<?php

/**
 * @Package:    MaplePHP - Error Text handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\SeverityLevelPool;
use Throwable;

class TextHandler extends AbstractHandler
{
    /**
     * Exception handler output
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $this->getHttp()->response()->getBody()->write("<pre>" . $this->getErrorMessage($exception) . "</pre>");
        $this->emitter($exception);
    }

    /**
     * Generate error message
     * @param Throwable $exception
     * @return string
     */
    protected function getErrorMessage(Throwable $exception): string
    {
        $traceLine = "#%s %s(%s): %s(%s)";
        $msg = "<strong>PHP Fatal error:</strong>  Uncaught exception '%s (%s)' with message '%s' in %s:<strong>%s</strong>\nStack trace:\n%s\n  thrown in %s on <strong>line %s</strong>";

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
        $result[] = '#' . ($key+1) . ' {main}';

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
        );
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
