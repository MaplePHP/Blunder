<?php

/**
 * @Package:    MaplePHP - Exception item
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder;

use Throwable;

class ExceptionItem
{
    private int $flag;
    private Throwable $exception;
    private SeverityLevelPool $pool;

    public function __construct(Throwable $exception, ?SeverityLevelPool $pool = null)
    {
        $this->exception = $exception;
        $this->flag = (method_exists($exception, "getSeverity")) ? $exception->getSeverity() : 0;
        if(is_null($pool)) {
            $pool = new SeverityLevelPool();
        }
        $this->pool = $pool;
    }

    /**
     * Will return error message
     * @return string
     */
    public function __toString(): string
    {
        return $this->exception->getMessage();
    }

    /**
     * Access the exception
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call(string $name, array $args): mixed
    {
        if(!method_exists($this->exception, $name)) {
            throw new \BadMethodCallException("Method '$name' does not exist in Throwable class");
        }

        return $this->exception->{$name}(...$args);
    }

    /**
     * Get Exception
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * Get exception type
     * @return string
     */
    public function getType(): string
    {
        return get_class($this->exception);
    }

    /**
     * Delete a severity level
     * @return bool
     */
    public function deleteSeverity(): bool
    {
        return $this->pool->deleteSeverityLevel($this->flag);
    }

    /**
     * Check if error type is supported
     * @return bool
     */
    public function hasSeverity(): bool
    {
        return ($this->pool->has($this->flag) !== false);
    }

    /**
     * Get severity level title name if severity is callable else will return exception type
     * @return string|null
     */
    public function getSeverity(): ?string
    {
        if($this->flag === 0) {
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
     * Check if error is an fatal error
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
