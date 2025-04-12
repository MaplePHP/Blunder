<?php

/**
 * Class SilentHandler
 *
 * Silently handles all non-fatal exceptions without outputting them,
 * while still allowing them to be captured via the event callback mechanism.
 * Fatal errors can optionally be shown using the constructor flag.
 *
 * Useful for production environments or unit tests where non-critical
 * errors should not interrupt the application flow but still be traceable.
 *
 * @package    MaplePHP\Blunder\Handlers
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use ErrorException;
use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use Throwable;

class SilentHandler extends TextHandler implements HandlerInterface
{
    protected bool $showFatalErrors;
    protected static bool $enabledTraceLines = false;

    /**
     * The SilentHandler will Silence all none fatal errors
     * You can still catch all errors in the event callable e.g. $run->event(function)
     */
    public function __construct(bool $showFatalErrors = false)
    {
        // I am turning off thrown exception and make regular call to exceptionHandler
        $this->throwException = false;
        $this->showFatalErrors = $showFatalErrors;
    }

    /**
     * Exception handler output
     *
     * @param Throwable $exception
     * @return void
     * @throws ErrorException
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $exceptionItem = new ExceptionItem($exception);
        if($this->showFatalErrors && ($exceptionItem->isLevelFatal() || $exceptionItem->getStatus() === "error")) {
            // Event is trigger inside "exceptionHandler".
            parent::exceptionHandler($exception);
        } else {
            if(is_callable($this->eventCallable)) {
                call_user_func_array($this->eventCallable, [$exceptionItem, $this->http]);
            }
        }
    }
}
