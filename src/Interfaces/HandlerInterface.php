<?php

/**
 * Interface HandlerInterface
 *
 * Marker interface for all concrete Blunder error handlers.
 * Used to type-hint and validate handler compatibility at runtime,
 * ensuring that a handler follows the expected structure derived from AbstractHandlerInterface.
 *
 * Commonly implemented by: HtmlHandler, JsonHandler, PlainTextHandler, etc.
 *
 * @package    MaplePHP\Blunder\Interfaces
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder\Interfaces;

use Throwable;

interface HandlerInterface
{
    public function exceptionHandler(Throwable $exception): void;
}
