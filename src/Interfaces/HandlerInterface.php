<?php

/**
 * @Package:    MaplePHP - Input validation interface
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Interfaces;

use ErrorException;
use Closure;
use Throwable;

interface HandlerInterface
{
    /**
     * Pass on a PSR response instance instead of creating a new one
     * @param HttpMessagingInterface $http
     * @return void
     */
    public function setHttp(HttpMessagingInterface $http): void;

    /**
     * Get PSR-7 HTTP message instance
     * @return HttpMessagingInterface
     */
    public function getHttp(): HttpMessagingInterface;

    /**
     * Sets a user-defined exception handler function
     * @throws ErrorException
     */
    public function exceptionHandler(Throwable $exception): void;

    /**
     * Sets a user-defined error handler function
     * @param int $errNo
     * @param string $errStr
     * @param string $errFile
     * @param int $errLine
     * @return mixed
     */
    public function errorHandler(int $errNo, string $errStr, string $errFile, int $errLine): mixed;

    /**
     * Register a function for execution on shutdown
     * @return void
     */
    public function shutdownHandler(): void;

    /**
     * The event callable will be triggered when an error occur.
     * Note: Will add PSR-14 support for dispatch in the future.
     * @param Closure $event
     * @return void
     */
    public function event(Closure $event): void;

    /**
     * Set expected severity mask
     * @param int $severity
     * @return self
     */
    public function setSeverity(int $severity): self;
}
