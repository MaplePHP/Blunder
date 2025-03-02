<?php

/**
 * @Package:    MaplePHP - Severity level pool
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder;

use Closure;
use InvalidArgumentException;
use MaplePHP\Blunder\Interfaces\HandlerInterface;

class SeverityLevelPool
{
    // List all supported error types
    protected const SEVERITY_TYPES = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        E_ALL => 'E_ALL'
    ];

    private array $allowedSeverityTypes = [];
    private array $removedSeverityTypes = [];
    private ?Closure $redirectCall = null;

    public function __construct(?array $allowedSeverityTypes = null)
    {
        if(is_array($allowedSeverityTypes)) {
            $this->setSeverityLevels($allowedSeverityTypes);
        } else {
            $this->allowedSeverityTypes = array_keys(self::SEVERITY_TYPES);
        }
    }

    /**
     * Get severity level as a title
     *
     * @param int $level Expected level code (e.g. E_WARNING)
     * @param string|null $fallback
     * @return string|null
     */
    public static function getSeverityLevel(int $level, ?string $fallback = null): ?string
    {
        return (self::SEVERITY_TYPES[$level] ?? $fallback);
    }

    /**
     * List all severities that can be used
     *
     * @return array
     */
    public static function listAll(): array
    {
        return self::SEVERITY_TYPES;
    }

    /**
     * Overwrite the default severity list and set a new one
     *
     * @param array $allowedSeverityTypes
     * @return self
     */
    public function setSeverityLevels(array $allowedSeverityTypes): self
    {
        $this->validate($allowedSeverityTypes);
        $this->allowedSeverityTypes = $allowedSeverityTypes;
        return $this;
    }

    /**
     * You can choose to redirect the removed severity
     *
     * @param Closure $call
     * @return $this
     */
    public function redirectTo(Closure $call): self
    {
        $this->allowedSeverityTypes = array_merge($this->allowedSeverityTypes, $this->removedSeverityTypes);
        $this->redirectCall = function (
            int $errNo, string $errStr, string $errFile, int $errLine = 0, array $context = []
        ) use ($call): bool|null|HandlerInterface
        {
            return $call($errNo, $errStr, $errFile, $errLine, $context);
        };
        return $this;
    }

    /**
     * Return the redirect closure
     *
     * @return Closure|null
     */
    public function getRedirectCall(): ?Closure
    {
        return $this->redirectCall;
    }

    /**
     * Check if severity has been removed
     *
     * @param int $level
     * @return bool
     */
    public function hasRemovedSeverity(int $level): bool
    {
        return $this->has($level, true);
    }

    /**
     * Exclude severity types from list expected severity list
     * When excluding the E_ALL flag will also be removed automatically
     *
     * @param array $exclude
     * @return self
     */
    public function excludeSeverityLevels(array $exclude): self
    {
        $this->validate($exclude);
        $this->deleteSeverityLevel(E_ALL);
        foreach($exclude as $severityLevel) {
            $this->deleteSeverityLevel((int)$severityLevel);
        }
        return $this;
    }

    /**
     * Delete a severity level
     *
     * @param int $flag
     * @return bool
     */
    public function deleteSeverityLevel(int $flag): bool
    {
        if(($key = $this->has($flag)) !== false) {
            $this->removedSeverityTypes[$key] = $flag;
            unset($this->allowedSeverityTypes[$key]);
            return true;
        }
        return false;
    }

    /**
     * Check if error type is supported
     *
     * @param int $flag Expected level code (e.g. E_WARNING)
     * @param bool $deleted
     * @return false|int|string
     */
    public function has(int $flag, bool $deleted = false): false|int|string
    {
        $type = ($deleted) ? $this->removedSeverityTypes : $this->allowedSeverityTypes;
        return array_search($flag, $type);
    }

    /**
     * Get severity mask
     *
     * @return int
     */
    public function getSeverityLevelMask(): int
    {
        if($this->has(E_ALL) !== false) {
            return E_ALL;
        }

        $error_mask = 0;
        foreach ($this->allowedSeverityTypes as $warning) {
            $error_mask |= (int)$warning;
        }

        return $error_mask;
    }

    /**
     * List all severities that can be used
     *
     * @return array
     */
    public function listAllSupported(): array
    {
        return $this->allowedSeverityTypes;
    }

    /**
     * Check if is a fatal error
     *
     * @param int $level
     * @return bool
     */
    final public function isLevelFatal(int $level): bool
    {
        $errors = E_ERROR;
        $errors |= E_PARSE;
        $errors |= E_CORE_ERROR;
        $errors |= E_CORE_WARNING;
        $errors |= E_COMPILE_ERROR;
        $errors |= E_COMPILE_WARNING;

        return ($level & $errors) > 0;
    }

    /**
     * Validate severity and see if is allowed
     *
     * @param int|array $level Expected level code:/s (e.g. E_WARNING)
     * @return bool
     */
    final protected function validate(int|array $level): bool
    {
        if(!is_array($level)) {
            $level = [$level];
        }
        return $this->validateMultiple($level);
    }

    /**
     * Validate severity and see if is allowed
     *
     * @param array $levels
     * @return bool
     */
    private function validateMultiple(array $levels): bool
    {
        foreach($levels as $level) {
            $level = (int)$level;
            if($this->has($level) === false) {
                throw new InvalidArgumentException("The severity level '$level' does not exist.");
            }
        }
        return true;
    }
}
