<?php

/**
 * @Package:    MaplePHP - Error handler framework
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder;

use MaplePHP\Blunder\Interfaces\HandlerInterface;
use MaplePHP\Blunder\Interfaces\HttpMessagingInterface;
use Closure;

class Run
{
    private HandlerInterface $handler;
    private ?SeverityLevelPool $severity = null;

    public function __construct(HandlerInterface $handler, ?HttpMessagingInterface $http = null)
    {
        $this->handler = $handler;
        if(!is_null($http)) {
            $this->handler->setHttp($http);
        }
    }

    /**
     * Access severity instance to set or exclude severity levels from error handler
     * @return SeverityLevelPool
     */
    public function severity(): SeverityLevelPool
    {
        if(is_null($this->severity)) {
            $this->severity = new SeverityLevelPool();
        }
        return $this->severity;
    }

    /**
     * The event callable will be triggered when an error occur.
     * Note: Will add PSR-14 support for dispatch in the future.
     * @param Closure $event
     * @return void
     */
    public function event(Closure $event): void
    {
        $this->handler->event($event);
    }

    /**
     * Init the handlers
     * @return void
     */
    public function load(): void
    {
        if (!headers_sent()) {
            header_remove('location');
        }

        set_error_handler([$this->handler, "errorHandler"], $this->severity()->getSeverityLevelMask());
        set_exception_handler([$this->handler, "exceptionHandler"]);
        register_shutdown_function([$this->handler, "shutdownHandler"]);
    }

}
