<?php

/**
 * Class CliHandler
 *
 * Handles exceptions for CLI environments by outputting a colorized and formatted
 * ANSI-compatible error message. Includes file info, severity, stack trace (optional),
 * and message formatting for improved readability in terminal applications.
 *
 * Inherits from TextHandler and uses the Ansi helper class for terminal styling.
 * Ideal for command-line tools, artisan scripts, or cron jobs.
 *
 * @package    MaplePHP\Blunder\Handlers
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Exceptions\BlunderSoftException;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use MaplePHP\Blunder\SeverityLevelPool;
use MaplePHP\Prompts\Themes\Ansi;
use Throwable;

final class CliHandler extends TextHandler implements HandlerInterface
{
    protected static ?Ansi $ansi = null;
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
        $this->getHttp()->response()->getBody()->write($this->getErrorMessage($exceptionItem));
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

        $msg = "\n";
        $msg .= self::ansi()->red("%s ") . self::ansi()->italic("(%s)") . ": ";
        $msg .= self::ansi()->bold("%s ") . " \n\n";
        $msg .= self::ansi()->bold("File: ") . "%s:" . self::ansi()->bold("%s") . "\n\n";
        //$severityLevel = $exception->getSeverity();

        $result = [];
        if (self::$enabledTraceLines) {
            $trace = $exception->getTrace($this->getMaxTraceLevel());
            $result = $this->getTraceResult($trace);
            $msg .= self::ansi()->bold("Stack trace:") . "\n";
            $msg .= "%s\n";
        }

        $message = preg_replace('/[^\S\n]+/', ' ', (string)$exception->getMessage());
        if($exception->getException() instanceof BlunderSoftException) {
            return self::ansi()->style(["bold", "red"], "Notice: ") . $message;
        }
        return sprintf(
            $msg,
            get_class($exception->getException()),
            (string)$exception->getSeverityConstant(),
            $message,
            $exception->getFile(),
            $exception->getLine(),
            implode("\n", $result),
            $exception->getFile(),
            $exception->getLine()
        )."\n";
    }

    /**
     * Get trace result
     * @param array $traceArr
     * @return array
     */
    protected function getTraceResult(array $traceArr): array
    {
        $key = 0;
        $result = [];
        $traceLine = self::ansi()->bold("#%s ") . "%s:" . self::ansi()->bold("%s") . ": %s(%s)";
        foreach ($traceArr as $key => $stackPoint) {
            if (is_array($stackPoint)) {
                $args = is_array($stackPoint['args']) ? $stackPoint['args'] : [];
                $result[] = sprintf(
                    $traceLine,
                    $key,
                    (string)($stackPoint['file'] ?? "0"),
                    (string)($stackPoint['line'] ?? "0"),
                    $this->getTracedMethodName($stackPoint),
                    implode(', ', $args)
                );
            }
        }
        // trace always ends with {main}
        $result[] = self::ansi()->bold('#' . ((int)$key + 1)). ' {main}';

        return $result;
    }

    /**
     * Get traced method name
     *
     * @param array $stackPoint
     * @return string
     */
    protected function getTracedMethodName(array $stackPoint): string
    {
        $class = ($stackPoint['class'] ?? '');
        $type = ($stackPoint['type'] ?? ':');
        $function = ($stackPoint['function'] ?? 'void');
        return "{$class}{$type}{$function}";
    }

    /**
     * Get ansi immutable instance
     * @return Ansi
     */
    protected static function ansi(): Ansi
    {
        if (self::$ansi === null) {
            self::$ansi = new Ansi();
        }

        return self::$ansi;
    }
}
