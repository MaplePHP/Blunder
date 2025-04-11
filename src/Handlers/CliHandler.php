<?php

/**
 * @Package:    MaplePHP - Error Plain text handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\ExceptionMetadata;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use MaplePHP\Blunder\SeverityLevelPool;
use MaplePHP\Prompts\Ansi;
use Throwable;

class CliHandler extends TextHandler implements HandlerInterface
{
    protected static ?Ansi $ansi = null;
    protected static bool $enabledTraceLines = false;

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

        $meta = new ExceptionMetadata($exception);

        $msg = "\n";
        $msg .= self::ansi()->red("%s ") . self::ansi()->italic("(%s)") . ": ";
        $msg .= self::ansi()->bold("%s ") . " \n\n";
        $msg .= self::ansi()->bold("File: ") . "%s:(" . self::ansi()->bold("%s") . ")\n\n";
        $severityLevel = (method_exists($exception, "getSeverity") ? $exception->getSeverity() : 0);

        $result = [];
        if(self::$enabledTraceLines) {
            $trace = $meta->getTrace();
            $result = $this->getTraceResult($trace);
            $msg .= self::ansi()->bold("Stack trace:") . "\n";
            $msg .= "%s\n";
        }

        $message = preg_replace('/\s+/', ' ', $exception->getMessage());
        $message = wordwrap($message, 110);

        return sprintf(
            $msg,
            get_class($exception),
            (string)SeverityLevelPool::getSeverityLevel((int)$severityLevel, "Error"),
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
        $traceLine = self::ansi()->bold("#%s ") . "%s(" . self::ansi()->bold("%s") . "): %s(%s)";
        foreach ($traceArr as $key => $stackPoint) {
            if(is_array($stackPoint)) {
                $args = is_array($stackPoint['args']) ? $stackPoint['args'] : [];
                $result[] = sprintf(
                    $traceLine,
                    $key,
                    (string)($stackPoint['file'] ?? "0"),
                    (string)($stackPoint['line'] ?? "0"),
                    (string)($stackPoint['function'] ?? "void"),
                    implode(', ', $args)
                );
            }
        }
        // trace always ends with {main}
        $result[] = self::ansi()->bold('#' . ((int)$key + 1)). ' {main}';

        return $result;
    }

    /**
     * Get ansi immutable instance
     * @return Ansi
     */
    protected static function ansi(): Ansi
    {
        if(is_null(self::$ansi)) {
            self::$ansi = new Ansi();
        }

        return self::$ansi;
    }
}
