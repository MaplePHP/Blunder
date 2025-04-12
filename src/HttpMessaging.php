<?php

/**
 * Class HttpMessaging
 *
 * A PSR-7 messaging handler that provides access to standard HTTP
 * request and response interfaces. Allows passing custom instances
 * or generates default ones using MaplePHP HTTP components.
 *
 * Useful as a bridge between PSR-compliant libraries and error handling,
 * debugging, or general application-level HTTP workflows.
 *
 * @package    MaplePHP\Blunder
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */


namespace MaplePHP\Blunder;

use MaplePHP\Blunder\Interfaces\HttpMessagingInterface;
use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Environment;
use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Http\Interfaces\StreamInterface;
use MaplePHP\Http\Response;
use MaplePHP\Http\ServerRequest;
use MaplePHP\Http\Stream;
use MaplePHP\Http\Uri;

class HttpMessaging implements HttpMessagingInterface
{
    protected ?ResponseInterface $response = null;
    protected ?ServerRequestInterface $request = null;

    /**
     * Pass your own PSR-7 library HTTP message library instances
     * @param ResponseInterface|null $response
     * @param ServerRequestInterface|null $request
     */
    public function __construct(?ResponseInterface $response = null, ?ServerRequestInterface $request = null)
    {
        $this->response = $response;
        $this->request = $request;
    }

    /**
     * Get PSR response
     * @return ResponseInterface
     */
    public function response(): ResponseInterface
    {
        if(!($this->response instanceof ResponseInterface)) {
            $stream = new Stream(Stream::TEMP);
            $this->response = new Response($stream);
        }

        return $this->response;
    }

    /**
     * Get PSR request
     * @return ServerRequestInterface
     */
    public function request(): ServerRequestInterface
    {
        if(!($this->request instanceof ServerRequestInterface)) {
            $env = new Environment();
            $this->request = new ServerRequest(new Uri($env->getUriParts()), $env);
        }

        return $this->request;
    }

    /**
     * Inherit or create new stream instance from PSR-7
     * @param mixed|null $stream
     * @param string $permission
     * @return StreamInterface
     */
    public function stream(mixed $stream = null, string $permission = "r+"): StreamInterface
    {
        if(!is_null($stream)) {
            return new Stream($stream, $permission);
        }

        return $this->response()->getBody();
    }

}
