<?php

/**
 * @Package:    MaplePHP - Error Silent handler library -
 *              Will Silence all none fatal errors (you can tho still catch them in the event callable)
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

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
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $item = new ExceptionItem($exception);
        if($this->showFatalErrors && ($item->isLevelFatal() || $item->getStatus() === "error")) {
            // Event is trigger inside "exceptionHandler".
            parent::exceptionHandler($exception);
        } else {
            if(is_callable($this->eventCallable)) {
                call_user_func_array($this->eventCallable, [$item, $this->http]);
            }
        }
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
