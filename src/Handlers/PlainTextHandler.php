<?php

/**
 * Class PlainTextHandler
 *
 * Handles exceptions by outputting a stripped-down plain text message.
 * Suitable for CLI environments, APIs, or systems where HTML and JSON
 * output is not appropriate.
 *
 * Inherits formatting logic from TextHandler and ensures response bodies
 * are clean, readable, and free from markup.
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

class PlainTextHandler extends TextHandler implements HandlerInterface
{
    /**
     * Exception handler output
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $exceptionItem = new ExceptionItem($exception);
        $this->getHttp()->response()->getBody()->write(strip_tags($this->getErrorMessage($exceptionItem)));
        $this->emitter($exceptionItem);
    }
}
