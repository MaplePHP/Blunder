<?php

/**
 * @Package:    MaplePHP - Error Plain text handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use Throwable;

class PlainTextHandler extends TextHandler
{
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
