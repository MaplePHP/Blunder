<?php

/**
 * Interface HttpMessagingInterface
 *
 * Defines the contract for interacting with PSR-7 HTTP message components within Blunder.
 * Ensures consistent access to request, response, and stream objects,
 * whether injected externally or created internally by the error handler.
 *
 * Implementations like `HttpMessaging` allow handlers to remain PSR-7 compatible
 * while abstracting away underlying HTTP message details.
 *
 * @package    MaplePHP\Blunder\Interfaces
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

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
