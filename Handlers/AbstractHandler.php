<?php

/**
 * @Package:    MaplePHP - Error Abstract extendable handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use ErrorException;
use MaplePHP\Blunder\HttpMessaging;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use MaplePHP\Blunder\Interfaces\HttpMessagingInterface;
use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\SeverityLevelPool;
use MaplePHP\Http\Interfaces\StreamInterface;
use Closure;
use Throwable;

abstract class AbstractHandler implements HandlerInterface
{
    /**
     * Maximum trace depth (memory improvement)
     * @var int
     */
    protected const MAX_TRACE_LENGTH = 40;

    protected bool $throwException = true;
    protected ?HttpMessagingInterface $http = null;
    protected ?Closure $eventCallable = null;
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
        if(!($this->http instanceof HttpMessagingInterface)) {
            $this->http = new HttpMessaging();
        }

        return $this->http;
    }

    /**
     * Set expected severity mask
     * @param int $severity
     * @return self
     */
    final public function setSeverity(int $severity): self
    {
        $this->severity = $severity;
        return $this;
    }

    /**
     * Main error handler script
     * @param int $errNo
     * @param string $errStr
     * @param string $errFile
     * @param int $errLine
     * @param array $context
     * @return bool
     * @throws ErrorException
     */
    public function errorHandler(int $errNo, string $errStr, string $errFile, int $errLine = 0, array $context = []): bool
    {
        if ($errNo & error_reporting()) {
            $this->cleanOutputBuffers();
            $exception = new ErrorException($errStr, 0, $errNo, $errFile, $errLine);
            if ($this->throwException) {
                throw $exception;
            } else {
                $this->exceptionHandler($exception);
            }
            return true;
        }
        return false;
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
            $new[$key]['args'] = array_map('gettype', (array)$new[$key]['args']);
            if($key >= (static::MAX_TRACE_LENGTH - 1)) {
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
        $this->cleanOutputBuffers();

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

        // Exit execute to prevent response under to be triggered in some cases
        exit();
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
    final protected function cleanOutputBuffers(): void
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
