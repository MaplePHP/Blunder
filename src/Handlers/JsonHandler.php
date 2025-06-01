<?php

/**
 * Class JsonHandler
 *
 * Handles exceptions by returning a structured JSON response with details
 * such as status, message, severity, trace, and file/line metadata.
 *
 * Designed for use in APIs or systems where JSON is the preferred output format.
 * Integrates with PSR-7 response interfaces and supports trace depth control.
 *
 * @package    MaplePHP\Blunder\Handlers
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use Throwable;

final class JsonHandler extends AbstractHandler implements HandlerInterface
{
    protected static bool $enabledTraceLines = true;

    /**
     * Exception handler output
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $exceptionItem = new ExceptionItem($exception);
        $trace = $exceptionItem->getTrace($this->getMaxTraceLevel());

        $jsonString = json_encode([
            "status" => $exceptionItem->getStatus(),
            "message" => $exception->getMessage(),
            "flag" => $exceptionItem->getSeverityConstant(),
            "file" => $exception->getFile(),
            "line" => $exception->getLine(),
            "code" => $exception->getCode(),
            "trace" => $trace,
        ]);

        if ($jsonString === false) {
            throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg(), json_last_error());
        }

        $this->getHttp()->response()->getBody()->write($jsonString);
        $this->getHttp()->response()->withHeader('content-type', 'application/json; charset=utf-8');
        $this->emitter($exceptionItem);
    }
}
