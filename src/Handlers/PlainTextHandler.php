<?php

/**
 * @Package:    MaplePHP - Error Plain text handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\Interfaces\HandlerInterface;
use Throwable;

class PlainTextHandler extends TextHandler implements HandlerInterface
{
    protected static bool $enabledTraceLines = true;

    /**
     * Exception handler output
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $this->getHttp()->response()->getBody()->write(strip_tags($this->getErrorMessage($exception)));
        $this->emitter($exception);
    }
}
