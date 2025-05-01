<?php

/**
 * Class ExceptionItem
 *
 * ExceptionItem wraps a Throwable and enriches it with severity context.
 * It provides utilities to access exception details, determine error levels,
 * generate PSR-3 compatible status messages, and return filtered stack traces.
 * Useful for structured error handling and logging.
 *
 * @package    MaplePHP\Blunder
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder;

use BadMethodCallException;
use ErrorException;
use MaplePHP\Blunder\Enums\BlunderErrorType;
use Throwable;

/**
 * @method string getMessage()
 * @method string getFile()
 * @method int getLine()
 */
final class ExceptionItem
{
    private int $flag;
    private Throwable $exception;
    private SeverityLevelPool $pool;

    private ?BlunderErrorType $severityError;

    public function __construct(Throwable $exception, ?SeverityLevelPool $pool = null)
    {
        $this->exception = $exception;
        $this->severityError = BlunderErrorType::fromErrorLevel(1);
        $this->flag = (method_exists($exception, "getSeverity")) ? $exception->getSeverity() : 0;
        if (is_null($pool)) {
            $pool = new SeverityLevelPool();
        }
        $this->pool = $pool;
    }

    /**
     * Will return an error message
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->exception->getMessage();
    }

    /**
     * Access the exception
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call(string $name, array $args): mixed
    {
        if (!method_exists($this->exception, $name)) {
            throw new BadMethodCallException("Method '$name' does not exist in Throwable class");
        }
        return $this->exception->{$name}(...$args);
    }

    /**
     * Get Exception
     *
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * Get Severity Pool
     *
     * @return SeverityLevelPool
     */
    public function getSeverityPool(): SeverityLevelPool
    {
        return $this->pool;
    }

    /**
     * Get an exception type
     *
     * @return string
     */
    public function getType(): string
    {
        return get_class($this->exception);
    }

    /**
     * Delete a severity level
     *
     * @return bool
     */
    public function deleteSeverity(): bool
    {
        return $this->pool->deleteSeverityLevel($this->flag);
    }

    /**
     * Check if an error type is supported
     *
     * @return bool
     */
    public function hasSeverity(): bool
    {
        return ($this->pool->has($this->flag) !== false);
    }

    /**
     * Get severity level title name if severity is callable else will return an exception type
     *
     * @return string|null
     */
    public function getSeverity(): ?string
    {
        if ($this->flag === 0) {
            return $this->getType();
        }

        return SeverityLevelPool::getSeverityLevel($this->flag);
    }

    /**
     * Get severity level title name
     * @return int
     */
    public function getMask(): int
    {
        return $this->flag;
    }

    /**
     * This will return a status for severity flag that will follow and return a
     * status title that follows PSR-3 log for easily logging errors
     *
     * @return string
     */
    public function getStatus(): string
    {
        return match ($this->flag) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, 0 => "error",
            E_WARNING, E_USER_WARNING, E_COMPILE_WARNING => "warning",
            E_NOTICE, E_USER_NOTICE => "notice",
            E_DEPRECATED, E_USER_DEPRECATED => "info",
            default => "debug",
        };

    }

    /**
     * Will return an expected severity type as enum
     *
     * @return BlunderErrorType
     */
    public function getSeverityError(): BlunderErrorType
    {
        $this->severityError = BlunderErrorType::fromErrorLevel(1);
        if ($this->exception instanceof ErrorException) {
            $this->severityError = BlunderErrorType::fromErrorLevel($this->exception->getSeverity());
        }
        return $this->severityError;
    }

    /**
     * Get severity flag title
     *
     * @return string|null
     */
    public function getSeverityConstant(): ?string
    {
        return $this->getSeverityError()->getErrorLevelKey();
    }

    /**
     * Create a title from exception severity
     *
     * @return string
     */
    public function getSeverityTitle(): string
    {
        return $this->getSeverityError()->getErrorLevelTitle();
    }


    /**
     * Get a trace line with filtered arguments and max length
     *
     * @param int $maxTraceLevel
     * @return array
     */
    public function getTrace(int $maxTraceLevel = 0): array
    {
        $new = [];
        $trace = $this->exception->getTrace();
        $mainErrorClass = get_class($this->exception);

        // This will also place the main error to trace a list
        array_unshift($trace, $this->pollyFillException([
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'class' => get_class($this->exception)
        ]));

        foreach ($trace as $key => $stackPoint) {
            $class = isset($stackPoint['class']) ? (string) $stackPoint['class'] : "";
            $blunderErrorClass = "MaplePHP\Blunder\Handlers\AbstractHandler";

            /** @psalm-suppress RedundantCondition */
            if ($mainErrorClass !== $blunderErrorClass && $class !== $blunderErrorClass) {
                $new[$key] = $stackPoint;
                $new[$key]['args'] = array_map('gettype', (array)($new[$key]['args'] ?? []));
                if ($maxTraceLevel > 0 && $key >= ($maxTraceLevel - 1)) {
                    break;
                }
            }
        }
        return $new;
    }

    /**
     * Get an exception array with the right items
     *
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
     * Check if the error is a fatal error
     *
     * @return bool
     */
    final public function isLevelFatal(): bool
    {
        $errors = E_ERROR;
        $errors |= E_PARSE;
        $errors |= E_CORE_ERROR;
        $errors |= E_CORE_WARNING;
        $errors |= E_COMPILE_ERROR;
        $errors |= E_COMPILE_WARNING;

        return ($this->flag & $errors) > 0;
    }
}
