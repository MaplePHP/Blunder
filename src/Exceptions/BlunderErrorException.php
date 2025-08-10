<?php

/**
 * Class BlunderErrorException
 *
 * Extends PHP's ErrorException with support for a custom "pretty" message,
 * and allows preserving original exception file and line information when
 * rethrowing exceptions with modified types.
 *
 * Useful for enhanced exception handling, presentation, and debugging
 * within the Blunder framework.
 *
 * @package    MaplePHP\Blunder
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder\Exceptions;

use ErrorException;
use Exception;
use ReflectionClass;
use Throwable;

final class BlunderErrorException extends ErrorException
{
    protected ?string $prettyMessage = null;

    /*
     public function __construct(
        string $message = "",
        int $code = 0,
        int $severity = E_ERROR,
        string $file = __FILE__,
        int $line = __LINE__,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $severity, $file, $line, $previous);
    }
    */

    /**
     * Will return the default ErrorException message
     *
     * @return string|null
     */
    public function getPrettyMessage(): ?string
    {
        return ($this->prettyMessage !== null) ? $this->prettyMessage : $this->message;
    }

    /**
     * Set pretty message that can be used in exception handlers
     *
     * @param string $message
     * @return void
     */
    public function setPrettyMessage(string $message): void
    {
        $this->prettyMessage = $message;
    }

    /**
     * Preserves the original exception's file and line number
     * when rethrowing with a different exception type.
     *
     * @param Exception $exception The new exception instance that will receive the original file and line number.
     * @return void
     */
    public function preserveExceptionOrigin(Throwable $exception): void
    {
        $reflection = new ReflectionClass(Exception::class);
        $fileProp = $reflection->getProperty('file');
        $fileProp->setValue($exception, $this->getFile());
        $lineProp = $reflection->getProperty('line');
        $lineProp->setValue($exception, $this->getLine());
    }
}
