<?php

namespace MaplePHP\Blunder;

use Exception;
use ErrorException;
use ReflectionClass;
use Throwable;

class BlunderErrorException extends ErrorException
{
    protected ?string $prettyMessage = null;

    /**
     * Will return the default ErrorException message
     *
     * @return string|null
     */
    public function getPrettyMessage(): ?string
    {
        return (!is_null($this->prettyMessage)) ? $this->prettyMessage : $this->message;
    }

    /**
     * Set pretty message that can be used in execption handlers
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
