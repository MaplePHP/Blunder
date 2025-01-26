<?php

/**
 * @Package:    MaplePHP - Error Json handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\ExceptionItem;
use Throwable;

class JsonHandler extends AbstractHandler
{
    /**
     * Exception handler output
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $trace = $this->getTrace($exception);
        $exceptionItem = new ExceptionItem($exception);
        $this->getHttp()->response()->getBody()->write(json_encode([
            "status" => $exceptionItem->getStatus(),
            "message" => $exception->getMessage(),
            "flag" => $exceptionItem->getSeverity(),
            "file" => $exception->getFile(),
            "line" => $exception->getLine(),
            "code" => $exception->getCode(),
            "trace" => $trace,
        ]));
        $this->getHttp()->response()->withHeader('content-type', 'application/json; charset=utf-8');
        $this->emitter($exception, $exceptionItem);
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
