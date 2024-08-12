<?php

/**
 * @Package:    MaplePHP - Input validation interface
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Interfaces;

use ErrorException;
use Throwable;

interface HandlerInterface
{
    /**
     * Pass on a PSR response instance instead of creating a new one
     * @param HttpMessagingInterface $http
     * @return void
     */
    function setHttp(HttpMessagingInterface $http): void;

    /**
     * Get PSR-7 HTTP message instance
     * @return HttpMessagingInterface
     */
    function getHttp(): HttpMessagingInterface;

    /**
     * Main error exception handler method
     * @throws ErrorException
     */
    public function exceptionHandler(Throwable $exception): void;
}
