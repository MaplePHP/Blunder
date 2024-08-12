<?php

/**
 * @Package:    MaplePHP - Input validation interface
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Interfaces;

use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Http\Interfaces\StreamInterface;

interface HttpMessagingInterface
{
    /**
     * Get PSR response
     * @return ResponseInterface
     */
    public function response(): ResponseInterface;

    /**
     * Get PSR request
     * @return ServerRequestInterface
     */
    public function request(): ServerRequestInterface;

    /**
     * Inherit or create new stream instance from PSR-7
     * @param mixed|null $stream
     * @param string $permission
     * @return StreamInterface
     */
    public function stream(mixed $stream = null, string $permission = "r+"): StreamInterface;
}
