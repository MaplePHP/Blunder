<?php

/**
 * @Package:    MaplePHP - Severity level pool
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder;

use InvalidArgumentException;

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

    private array $allowedSeverityTypes;

    function __construct(?array $allowedSeverityTypes = null)
    {
        if(is_array($allowedSeverityTypes)) {
            $this->setSeverityLevels($allowedSeverityTypes);
        } else {
            $this->allowedSeverityTypes = array_keys(self::SEVERITY_TYPES);
        }
    }

    /**
     * Get severity level as a title
     * @param int $level Expected level code (e.g. E_WARNING)
     * @param string|null $fallback
     * @return string|null
     */
    static public function getSeverityLevel(int $level, ?string $fallback = null): ?string
    {
        return (self::SEVERITY_TYPES[$level] ?? $fallback);
    }

    /**
     * List all severities that can be used
     * @return array
     */
    static public function listAll(): array
    {
        return self::SEVERITY_TYPES;
    }

    /**
     * Overwrite the default severity list and set a new one
     * @param array $allowedSeverityTypes
     * @return void
     */
    public function setSeverityLevels(array $allowedSeverityTypes): void
    {
        $this->validate($allowedSeverityTypes);
        $this->allowedSeverityTypes = $allowedSeverityTypes;
    }

    /**
     * Exclude severity types from list expected severity list
     * When excluding E_ALL will auto remove
     * @param array $exclude
     * @return void
     */
    public function excludeSeverityLevels(array $exclude): void
    {
        $this->validate($exclude);
        $this->deleteSeverityLevel(E_ALL);
        foreach($exclude as $severityLevel) {
            $this->deleteSeverityLevel($severityLevel);
        }
    }

    /**
     * Delete a severity level
     * @param int $flag
     * @return bool
     */
    public function deleteSeverityLevel(int $flag): bool
    {
        if(($key = $this->has($flag)) !== false) {
            unset($this->allowedSeverityTypes[$key]);
            return true;
        }
        return false;
    }

    /**
     * Check if error type is supported
     * @param int $flag Expected level code (e.g. E_WARNING)
     * @return false|int|string
     */
    public function has(int $flag): false|int|string
    {
        return array_search($flag, $this->allowedSeverityTypes);
    }

    /**
     * Get severity mask
     * @return int
     */
    public function getSeverityLevelMask(): int
    {
        if($this->has(E_ALL) !== false) {
            return E_ALL;
        }

        $error_mask = 0;
        foreach ($this->allowedSeverityTypes as $warning) {
            $error_mask |= $warning;
        }
        return $error_mask;
    }

    /**
     * List all severities that can be used
     * @return array
     */
    public function listAllSupported(): array
    {
        return $this->allowedSeverityTypes;
    }

    /**
     * Check if is a fatal error
     * @param $level
     * @return bool
     */
    final public function isLevelFatal($level): bool
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
     * @param array $levels
     * @return bool
     */
    private function validateMultiple(array $levels): bool
    {
        foreach($levels as $level) {
            if($this->has($level) === false) {
                throw new InvalidArgumentException("The severity level '$level' does not exist.");
            }
        }
        return true;
    }
}
