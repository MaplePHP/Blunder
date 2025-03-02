<?php

/**
 * @Package:    MaplePHP - Error handler framework
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder;

use MaplePHP\Blunder\Interfaces\AbstractHandlerInterface;
use MaplePHP\Blunder\Interfaces\HttpMessagingInterface;
use Closure;

class Run
{
    private AbstractHandlerInterface $handler;
    private ?SeverityLevelPool $severity = null;
    private bool $removeLocationHeader = false;

    public function __construct(AbstractHandlerInterface $handler, ?HttpMessagingInterface $http = null)
    {
        $this->handler = $handler;
        if(!is_null($http)) {
            $this->handler->setHttp($http);
        }
    }

    /**
     * You can disable exit code 1 so Blunder can be used in test cases
     *
     * @param int $code
     * @return $this
     */
    public function setExitCode(int $code): self
    {
        $this->handler->setExitCode($code);
        return $this;
    }

    /**
     * Enable or disable the removal of the 'Location' header to prevent redirections.
     *
     * @param bool $removeRedirect
     * @return $this
     */
    public function removeLocationHeader(bool $removeRedirect): self
    {
        $this->removeLocationHeader = $removeRedirect;
        return $this;
    }

    /**
     * Access severity instance to set or exclude severity levels from error handler
     *
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
     *
     * @param Closure $event
     * @return void
     */
    public function event(Closure $event): void
    {
        $this->handler->event($event);
    }

    /**
     * Init the handlers
     *
     * @return void
     */
    public function load(): void
    {
        if ($this->removeLocationHeader && !headers_sent()) {
            header_remove('location');
        }

        // Will actually clear unwanted output in some cases
        ob_start();
        $this->handler->setSeverity($this->severity());
        set_error_handler([$this->handler, "errorHandler"], $this->severity()->getSeverityLevelMask());
        set_exception_handler([$this->handler, "exceptionHandler"]);
        register_shutdown_function([$this->handler, "shutdownHandler"]);
        ob_clean();
    }

}
