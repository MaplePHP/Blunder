<?php

namespace MaplePHP\Blunder;

use ErrorException;
use MaplePHP\Blunder\Enums\BlunderErrorType;
use Throwable;

class ExceptionMetadata
{
    protected const MAX_TRACE_LEVEL = 40;
    private bool $enableTraceLevel = true;
    private ?int $maxTraceLevel = null;
    private throwable $exception;
    private ?BlunderErrorType $severityError = null;

    public function __construct(throwable $exception)
    {
        $this->exception = $exception;
        $this->severityError = BlunderErrorType::fromErrorLevel(1);
    }

    /**
     * Set a max trace level
     *
     * @param int $maxTraceLevel
     * @return $this
     */
    public function setMaxTraceLevel(int $maxTraceLevel): self
    {
        $this->maxTraceLevel = $maxTraceLevel;
        return $this;
    }

    /**
     * Disable trace levels, (is enabled by default)
     *
     * @param bool $disableTraceLevel
     * @return $this
     */
    public function disableTraceLevel(bool $disableTraceLevel = true): self
    {
        $this->enableTraceLevel = $disableTraceLevel;
        return $this;
    }

    /**
     * Get the max trace level
     *
     * @return int
     */
    protected function getMaxTraceLevel(): int
    {
        return !is_null($this->maxTraceLevel) ? $this->maxTraceLevel : static::MAX_TRACE_LEVEL;
    }

    /**
     * Get current exception
     *
     * @return Throwable
     */
    public function getException(): throwable
    {
        return $this->exception;
    }

    /**
     * Will return a expected severity type as enum
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
     * Create title from exception severity
     *
     * @return string|null
     */
    public function getSeverityTitle(): ?string
    {
        return $this->getSeverityError()->getErrorLevelTitle();
    }


    /**
     * Get trace line with filtered arguments and max length
     *
     * @return array
     */
    public function getTrace(): array
    {
        $new = [];
        $trace = $this->exception->getTrace();

        array_unshift($trace, $this->pollyFillException([
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'class' => get_class($this->exception)
        ]));

        foreach ($trace as $key => $stackPoint) {
            $new[$key] = $stackPoint;
            $new[$key]['args'] = array_map('gettype', (array)($new[$key]['args'] ?? []));
            if($key >= ($this->getMaxTraceLevel() - 1) || !$this->enableTraceLevel) {
                break;
            }
        }
        return $new;
    }

    /**
     * Get an exception array with right items
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

}