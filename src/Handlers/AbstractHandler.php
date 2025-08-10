<?php

/**
 * Class AbstractHandler
 *
 * Base class for all Blunder error handlers. Implements core logic for capturing,
 * redirecting, formatting, and emitting exceptions and errors using PSR-7 messaging.
 *
 * Provides default implementations for shutdown handling, error/exception management,
 * event triggering, and severity masking. Supports redirect-able error types and custom
 * exit codes. Intended to be extended by specific output handlers (e.g. JSON, HTML, etc.).
 *
 * @package    MaplePHP\Blunder\Handlers
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use BadMethodCallException;
use Closure;
use ErrorException;
use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Exceptions\BlunderErrorException;
use MaplePHP\Blunder\HttpMessaging;
use MaplePHP\Blunder\Interfaces\AbstractHandlerInterface;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use MaplePHP\Blunder\Interfaces\HttpMessagingInterface;
use MaplePHP\Blunder\SeverityLevelPool;
use MaplePHP\Http\Interfaces\StreamInterface;
use Throwable;

abstract class AbstractHandler implements AbstractHandlerInterface
{
    /**
     * Maximum trace depth (memory improvement)
     * @var int
     */
    protected const MAX_TRACE_LEVEL = 40;

    protected static ?int $exitCode = null;
    protected static bool $enabledTraceLines = true;

    protected bool $throwException = true;
    protected ?Throwable $exception = null;
    protected ?HttpMessagingInterface $http = null;
    protected ?Closure $eventCallable = null;
    protected ?SeverityLevelPool $severityLevelPool = null;
    protected int $severity = E_ALL;

    /**
     * Sets the exit code to be used when an error occurs.
     * If you want Blunder to trigger a specific exit code on error,
     * specify the code using this method.
     *
     * @param ?int $code The exit code to use.
     * @return $this
     */
    public function setExitCode(?int $code): self
    {
        self::$exitCode = $code;
        return $this;
    }

    /**
     * Will enable trance lines
     * @param bool $enable
     * @return $this
     */
    public function enableTraceLines(bool $enable): self
    {
        self::$enabledTraceLines = $enable;
        return $this;
    }


    /**
     * Get the max trace level
     *
     * @return int
     */
    protected function getMaxTraceLevel(): int
    {
        return (self::$enabledTraceLines) ? static::MAX_TRACE_LEVEL : 0;
    }

    /**
     * The event callable will be triggered when an error occur.
     * Note: Will add PSR-14 support for dispatch in the future.
     * @param Closure $event
     * @return void
     */
    public function event(Closure $event): void
    {
        $this->eventCallable = $event;
    }

    /**
     * Inherit your PSR-7 HTTP message instance instead of creating a new one
     * @param HttpMessagingInterface $http
     * @return void
     */
    public function setHttp(HttpMessagingInterface $http): void
    {
        $this->http = $http;
    }

    /**
     * Get PSR-7 HTTP message instance
     * @return HttpMessagingInterface
     */
    public function getHttp(): HttpMessagingInterface
    {
        if (!($this->http instanceof HttpMessagingInterface)) {
            $this->http = new HttpMessaging();
        }
        return $this->http;
    }

    /**
     * Will get valid stream
     *
     * @param mixed|null $stream
     * @param string $permission
     * @return StreamInterface
     */
    final protected function getStream(mixed $stream = null, string $permission = "r+"): StreamInterface
    {
        if ($this->http === null) {
            throw new BadMethodCallException("You Must initialize the stream before calling this method");
        }
        return $this->http->stream($stream, $permission);
    }

    /**
     * Get exception if has been initiated
     *
     * @return Throwable|null
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    /**
     * Set expected severity mask
     * @param SeverityLevelPool $severity
     * @return self
     */
    final public function setSeverity(SeverityLevelPool $severity): self
    {
        $this->severityLevelPool = $severity;
        $this->severity = $this->severityLevelPool->getSeverityLevelMask();
        return $this;
    }

    /**
     * Main error handler script
     *
     * @param int $errNo
     * @param string $errStr
     * @param string $errFile
     * @param int $errLine
     * @param array $context
     * @return bool
     * @throws Throwable
     */
    public function errorHandler(int $errNo, string $errStr, string $errFile, int $errLine = 0, array $context = []): bool
    {
        if ($errNo & error_reporting()) {
            // Redirect to PHP error
            $redirectHandler = $this->redirectExceptionHandler($errNo, $errStr, $errFile, $errLine, $context);
            if ($redirectHandler !== null) {
                return $redirectHandler;
            }
            /*
            if (in_array($errNo, [E_USER_WARNING, E_USER_NOTICE, E_WARNING, E_NOTICE], true)) {
                return true;
            }
             */
            $this->cleanOutputBuffers();
            $this->exception = new BlunderErrorException($errStr, 0, $errNo, $errFile, $errLine);
            if ($this->throwException) {
                $this->exception->setPrettyMessage($this->getErrorMessage($this->exception));
                throw $this->exception;
            } else {
                $this->exceptionHandler($this->exception);
            }
            return true;
        }
        return false;
    }

    /**
     * Handle the errorHandler or redirect to PHP error to a new handler or
     *
     * @param int $errNo
     * @param string $errStr
     * @param string $errFile
     * @param int $errLine
     * @param array $context
     * @return bool|null
     * @throws ErrorException
     */
    public function redirectExceptionHandler(
        int $errNo,
        string $errStr,
        string $errFile,
        int $errLine = 0,
        array $context = []
    ): null|bool {
        if ($this->severityLevelPool && $this->severityLevelPool->hasRemovedSeverity($errNo)) {
            $redirectCall = $this->severityLevelPool->getRedirectCall();
            if ($redirectCall !== null) {
                $ret = $redirectCall($errNo, $errStr, $errFile, $errLine, $context);
                if ($ret !== null) {
                    if ($ret instanceof HandlerInterface) {
                        $exception = new BlunderErrorException($errStr, 0, $errNo, $errFile, $errLine);
                        $ret->exceptionHandler($exception);
                        exit;
                    }
                    return !!$ret;
                }
            }
        }
        return null;
    }

    /**
     * Shutdown handler
     *
     * @throws Throwable
     */
    public function shutdownHandler(): void
    {
        $this->throwException = false;
        $error = error_get_last();
        if ($error) {
            $item = new ExceptionItem(new ErrorException());
            if ($item->isLevelFatal() && ($error['type'] & $this->severity) !== 0) {
                $this->errorHandler(
                    $error['type'],
                    $error['message'],
                    $error['file'],
                    $error['line']
                );
            }
        }
        $this->sendExitCode();
    }

    /**
     * Emit response
     *
     * @param ExceptionItem $exceptionItem
     * @return void
     */
    protected function emitter(ExceptionItem $exceptionItem): void
    {
        //$this->cleanOutputBuffers();
        if (!headers_sent()) {
            header_remove('location');
            header('HTTP/1.1 500 Internal Server Error');
        }
        $response = $this->getHttp()->response()->withoutHeader('location');
        $response->createHeaders();
        $response->executeHeaders();
        $stream = $response->getBody();

        if (is_callable($this->eventCallable)) {
            call_user_func_array($this->eventCallable, [$exceptionItem, $this->http]);
        }
        $stream->rewind();
        echo $stream->read((int)$stream->getSize());
        $this->sendExitCode();
    }

    /**
     * Will send an exit code if specified
     *
     * @return void
     */
    protected function sendExitCode(): void
    {
        if (self::$exitCode !== null) {
            exit(self::$exitCode);
        }
    }

    /**
     * Generate error message (placeholder)
     *
     * @param ExceptionItem|Throwable $exception
     * @return string
     */
    protected function getErrorMessage(ExceptionItem|Throwable $exception): string
    {
        return "";
    }

    /**
     * This will clean all active output buffers
     *
     * @return void
     */
    final public function cleanOutputBuffers(): void
    {
        if (ob_get_level() > 0) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }
}
