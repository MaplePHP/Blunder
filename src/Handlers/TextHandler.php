<?php

/**
 * Class TextHandler
 *
 * Handles exceptions by generating a preformatted plain-text error message
 * including a full stack trace and metadata about the exception.
 *
 * Useful for environments where simple text output is desired, such as CLI,
 * log files, or fallback renderers in case HTML/JSON output fails.
 * Also serves as a base class for other handlers like PlainTextHandler and SilentHandler.
 *
 * @package    MaplePHP\Blunder\Handlers
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use MaplePHP\Blunder\SeverityLevelPool;
use Throwable;

class TextHandler extends AbstractHandler implements HandlerInterface
{
    protected static bool $enabledTraceLines = true;

    /**
     * Exception handler output
     *
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $exceptionItem = new ExceptionItem($exception);
        $this->getHttp()->response()->getBody()->write("<pre>" . $this->getErrorMessage($exceptionItem) . "</pre>");
        $this->emitter($exceptionItem);
    }

    /**
     * Generate error message
     *
     * @param ExceptionItem|Throwable $exception
     * @return string
     */
    protected function getErrorMessage(ExceptionItem|Throwable $exception): string
    {
        if ($exception instanceof Throwable) {
            $exception = new ExceptionItem($exception);
        }

        $traceLine = "#%s %s(%s): %s(%s)";
        $msg = "<strong>PHP Fatal error:</strong>  Uncaught exception '%s (%s)' with message '%s' in %s:<strong>%s</strong>\nStack trace:\n%s\n  thrown in %s on <strong>line %s</strong>";

        $key = 0;
        $result = [];
        $trace = $exception->getTrace($this->getMaxTraceLevel());
        $severityLevel = $exception->getSeverity();

        foreach ($trace as $key => $stackPoint) {
            if (is_array($stackPoint)) {
                $result[] = sprintf(
                    $traceLine,
                    $key,
                    (string)($stackPoint['file'] ?? 0),
                    (string)($stackPoint['line'] ?? 0),
                    (string)($stackPoint['function'] ?? "void"),
                    implode(', ', (array)$stackPoint['args'])
                );
            }
        }

        // trace always ends with {main}
        $result[] = '#' . ((int)$key + 1) . ' {main}';

        // write trace-lines into main template
        return sprintf(
            $msg,
            get_class($exception->getException()),
            (string)SeverityLevelPool::getSeverityLevel((int)$severityLevel, "Error"),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode("\n", $result),
            $exception->getFile(),
            $exception->getLine()
        );
    }
}
