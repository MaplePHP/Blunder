<?php

/**
 * @Package:    MaplePHP - Error Abstract extendable handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use ErrorException;
use MaplePHP\Blunder\BlunderErrorException;
use MaplePHP\Blunder\Interfaces\AbstractHandlerInterface;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use MaplePHP\Blunder\Interfaces\HttpMessagingInterface;
use MaplePHP\Blunder\HttpMessaging;
use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\SeverityLevelPool;
use MaplePHP\Http\Interfaces\StreamInterface;
use Closure;
use Throwable;

abstract class AbstractHandler implements AbstractHandlerInterface
{
    /**
     * Maximum trace depth (memory improvement)
     * @var int
     */
    protected const MAX_TRACE_LENGTH = 40;

    protected static ?int $exitCode = null;
    protected static bool $enabledTraceLines = false;

    protected bool $throwException = true;
    protected ?Throwable $exception = null;
    protected ?HttpMessagingInterface $http = null;
    protected ?Closure $eventCallable = null;
    protected ?SeverityLevelPool $severityLevelPool = null;
    protected int $severity = E_ALL;

    /**
     * Determine how the code block should look like
     * @param array $data
     * @param string $code
     * @param int $index
     * @return string
     */
    abstract protected function getCodeBlock(array $data, string $code, int $index = 0): string;

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
            if(!is_null($redirectHandler)) {
                return $redirectHandler;
            }
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
     * Get exception if has been initiated
     * @return Throwable|null
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
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
    ): null|bool
    {
        if ($this->severityLevelPool->hasRemovedSeverity($errNo)) {
            $redirectCall = $this->severityLevelPool->getRedirectCall();
            $ret = $redirectCall($errNo, $errStr, $errFile, $errLine, $context);
            if(!is_null($ret)) {
                if ($ret instanceof HandlerInterface) {
                    $exception = new BlunderErrorException($errStr, 0, $errNo, $errFile, $errLine);
                    $ret->exceptionHandler($exception);
                    exit;
                }
                return $ret;
            }
        }
        return null;
    }

    /**
     * Shutdown handler
     * @throws ErrorException
     */
    public function shutdownHandler(): void
    {
        $this->throwException = false;
        $error = error_get_last();
        if($error) {
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
     * Get trace line with filtered arguments and max length
     * @param Throwable $exception
     * @return array
     */
    protected function getTrace(throwable $exception): array
    {
        $new = [];
        $trace = $exception->getTrace();

        array_unshift($trace, $this->pollyFillException([
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'class' => get_class($exception)
        ]));

        foreach ($trace as $key => $stackPoint) {
            $new[$key] = $stackPoint;
            $new[$key]['args'] = array_map('gettype', (array)($new[$key]['args'] ?? []));
            if($key >= (static::MAX_TRACE_LENGTH - 1) || !static::$enabledTraceLines) {
                break;
            }
        }

        return $new;
    }


    /**
     * Emit response
     * @param Throwable $exception
     * @param ExceptionItem|null $exceptionItem
     * @return void
     */
    protected function emitter(throwable $exception, ?ExceptionItem $exceptionItem = null): void
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

        if(is_callable($this->eventCallable)) {
            if(is_null($exceptionItem)) {
                $exceptionItem = new ExceptionItem($exception);
            }
            call_user_func_array($this->eventCallable, [$exceptionItem, $this->http]);
        }
        $stream->rewind();
        echo $stream->read((int)$stream->getSize());
        $this->sendExitCode();
    }

    /**
     * Will send a exit code if specied
     *
     * @return void
     */
    protected function sendExitCode(): void
    {
        if(!is_null(self::$exitCode)) {
            exit(self::$exitCode);
        }
    }

    /**
     * Get code between start and end span from file
     * @param StreamInterface $stream
     * @param int $errorLine
     * @param int $startSpan
     * @param int $endSpan
     * @return string
     */
    protected function getContentsBetween(StreamInterface $stream, int $errorLine, int $startSpan = 10, int $endSpan = 12): string
    {
        $index = 1;
        $output = '';
        $startLine = $errorLine - $startSpan;
        $endLine = $errorLine + $endSpan;
        if($startLine < 1) {
            $startLine = 1;
        }
        while (!$stream->eof()) {
            $line = $stream->read((int)$stream->getSize());
            $lines = explode("\n", $line);
            foreach ($lines as $lineContent) {
                if ($index >= $startLine && $index <= $endLine) {
                    $output .= '<span class="line-holder flex">';
                    $output .= '<span class="line-number">'. $index .'</span>';
                    if($errorLine === $index) {
                        $output .= "<span class='line line-active'>" . htmlspecialchars($lineContent) . "</span>\n";
                    } else {
                        $output .= "<span class='line'>" . htmlspecialchars($lineContent) . "</span>\n";
                    }
                    $output .= '</span>';
                }
                if ($index > $endLine) {
                    break;
                }
                $index++;
            }
        }

        return $output;
    }

    /**
     * Will return the severity exception breadcrumb
     * @param Throwable $exception
     * @return string
     */
    public function getSeverityBreadcrumb(throwable $exception): string
    {

        $severityTitle = $this->getSeverityTitle($exception);
        $breadcrumb = get_class($exception);
        if(!is_null($severityTitle)) {
            $breadcrumb .= " <span class='color-green'>($severityTitle)</span>";
        }

        return "<div class='text-base mb-10 color-darkgreen'>$breadcrumb</div>";
    }

    /**
     * Get severity flag title
     * @param Throwable $exception
     * @return string|null
     */
    final public function getSeverityTitle(throwable $exception): ?string
    {
        $severityTitle = null;
        if ($exception instanceof ErrorException) {
            $severityTitle = SeverityLevelPool::getSeverityLevel($exception->getSeverity(), "Error");
        }

        return $severityTitle;
    }

    /**
     * This will add the code block structure
     * If you wish to edit the block then you should edit the "getCodeBlock" method
     * @param array $trace
     * @return array
     */
    final protected function getTraceCodeBlock(array $trace): array
    {
        $block = [];
        foreach ($trace as $key => $stackPoint) {
            if(is_array($stackPoint) && isset($stackPoint['file']) && is_file((string)$stackPoint['file'])) {
                $stream = $this->getStream($stackPoint['file']);
                $code = $this->getContentsBetween($stream, (int)$stackPoint['line']);
                $block[] = $this->getCodeBlock($stackPoint, $code, $key);
                $stream->close();
            }
        }

        return $block;
    }

    /**
     * Used to fetch valid asset
     * @param string $file
     * @return string
     * @throws ErrorException
     */
    public function getAssetContent(string $file): string
    {
        $ending = explode(".", $file);
        $ending = end($ending);

        if(!($ending === "css" || $ending === "js")) {
            throw new ErrorException("Only JS and CSS files are allowed as assets files");
        }
        $filePath = (str_starts_with($file, "/") ? realpath($file) : realpath(__DIR__ . "/../") . "/" . $file);
        $stream = $this->getStream($filePath);

        return $stream->getContents();
    }

    /**
     * Generate error message
     * @param Throwable $exception
     * @return string
     */
    protected function getErrorMessage(Throwable $exception): string
    {
        $traceLine = "#%s %s(%s): %s(%s)";
        $msg = "PHP Fatal error:  Uncaught exception '%s (%s)' with message '%s' in %s:%s\nStack trace:\n%s\n thrown in %s on line %s";

        $key = 0;
        $result = [];
        $trace = $this->getTrace($exception);
        $severityLevel = (method_exists($exception, "getSeverity") ? $exception->getSeverity() : 0);
        foreach ($trace as $key => $stackPoint) {
            if(is_array($stackPoint)) {
                $result[] = sprintf(
                    $traceLine,
                    $key,
                    (string)($stackPoint['file'] ?? 0),
                    (string)($stackPoint['line'] ?? 0),
                    (string)($stackPoint['function'] ?? "void"),
                    implode(', ', (array)$stackPoint['args'])
                );
            }
        }

        $result[] = '#' . ((int)$key + 1) . ' {main}';
        return sprintf(
            $msg,
            get_class($exception),
            (string)SeverityLevelPool::getSeverityLevel((int)$severityLevel, "Error"),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode("\n", $result),
            $exception->getFile(),
            $exception->getLine()
        );
    }

    /**
     * Get an exception array with right items
     * @param array $arr
     * @return array
     */
    public function pollyFillException(array $arr): array
    {
        return array_merge([
            'file' => "",
            'line' => "",
            'class' => "",
            'function' => null,
            'type' => null,
            'args' => []
        ], $arr);
    }

    /**
     * This will clean all active output buffers
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

    /**
     * Will get valid stream
     * @param mixed|null $stream
     * @param string $permission
     * @return StreamInterface
     */
    final protected function getStream(mixed $stream = null, string $permission = "r+"): StreamInterface
    {
        if(is_null($this->http)) {
            throw new \BadMethodCallException("You Must initialize the stream before calling this method");
        }

        return $this->http->stream($stream, $permission);
    }

}
