<?php

namespace MaplePHP\Blunder\Enums;

enum BlunderErrorType
{
    case FALLBACK;
    case ERROR;
    case WARNING;
    case PARSE;
    case NOTICE;
    case CORE_ERROR;
    case CORE_WARNING;
    case COMPILE_ERROR;
    case COMPILE_WARNING;
    case USER_ERROR;
    case USER_WARNING;
    case USER_NOTICE;
    case RECOVERABLE_ERROR;
    case DEPRECATED;
    case USER_DEPRECATED;

    /**
     * A single source of truth for constant value, constant name, and title.
     *
     * @var array<string, array{int, string, string}>
     */
    private const MAP = [
        'FALLBACK' => [0, 'E_USER_ERROR', 'Fallback error'],
        'ERROR' => [E_ERROR, 'E_ERROR', 'Fatal error'],
        'WARNING' => [E_WARNING, 'E_WARNING', 'Warning'],
        'PARSE' => [E_PARSE, 'E_PARSE', 'Parse error'],
        'NOTICE' => [E_NOTICE, 'E_NOTICE', 'Notice'],
        'CORE_ERROR' => [E_CORE_ERROR, 'E_CORE_ERROR', 'Core fatal error'],
        'CORE_WARNING' => [E_CORE_WARNING, 'E_CORE_WARNING', 'Core warning'],
        'COMPILE_ERROR' => [E_COMPILE_ERROR, 'E_COMPILE_ERROR', 'Compile-time fatal error'],
        'COMPILE_WARNING' => [E_COMPILE_WARNING, 'E_COMPILE_WARNING', 'Compile-time warning'],
        'USER_ERROR' => [E_USER_ERROR, 'E_USER_ERROR', 'User fatal error'],
        'USER_WARNING' => [E_USER_WARNING, 'E_USER_WARNING', 'User warning'],
        'USER_NOTICE' => [E_USER_NOTICE, 'E_USER_NOTICE', 'User notice'],
        'RECOVERABLE_ERROR' => [E_RECOVERABLE_ERROR, 'E_RECOVERABLE_ERROR', 'Recoverable fatal error'],
        'DEPRECATED' => [E_DEPRECATED, 'E_DEPRECATED', 'Deprecated notice'],
        'USER_DEPRECATED' => [E_USER_DEPRECATED, 'E_USER_DEPRECATED', 'User deprecated notice'],
        'E_ALL' => [E_ALL, 'E_ALL', 'All'],
    ];

    
    /**
     * Retrieve all PHP constant values that are mapped, either preserving the original keys or as a flat list.
     *
     * @param bool $preserveKeys If true, retains the original keys. Otherwise, returns a flat indexed array.
     * @return array<int> List of PHP constant values.
     */
    public static function getAllErrorLevels(bool $preserveKeys = false): array
    {
        $items = self::MAP;
        array_shift($items);
        $arr = array_map(fn ($item) => $item[0], $items);
        if($preserveKeys) {
            return $arr;
        }
        return array_values($arr);
    }

    /**
     * Get the PHP constant value associated with this error type.
     *
     * @return int The constant value corresponding to the enum case.
     */
    public function getErrorLevel(): int
    {
        return self::MAP[$this->name][0];
    }

    
    /**
     * Get the PHP constant key (name) associated with this error type.
     *
     * @return string The constant key corresponding to the enum case.
     */
    public function getErrorLevelKey(): string
    {
        return self::MAP[$this->name][1];
    }


    /**
     * Get the user-friendly title for this error type.
     * If the error type is `FALLBACK`, a custom fallback title is returned.
     *
     * @param string $fallback Custom fallback title used if the error type is `FALLBACK`. Defaults to 'Error'.
     * @return string The user-friendly title associated with this error type.
     */
    public function getErrorLevelTitle(string $fallback = 'Error'): string
    {
        return $this === self::FALLBACK ? $fallback : self::MAP[$this->name][2];
    }

    /**
     * Get the enum instance corresponding to the specified error number.
     *
     * If the error number does not match any predefined case, it returns the default fallback case.
     *
     * @param int $errno The error number to find the corresponding enum case for.
     * @return self The matching enum case, or the fallback case if no match is found.
     */
    public static function fromErrorLevel(int $errno): self
    {
        $cases = self::cases();
        foreach ($cases as $case) {
            if ($case->getErrorLevel() === $errno) {
                return $case;
            }
        }
        return reset($cases);
    }
}
