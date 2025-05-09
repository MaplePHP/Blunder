<?php

/**
 * Class XmlHandler
 *
 * Handles exceptions by generating an XML-formatted response that includes
 * status, message, file, line number, code, and a detailed stack trace.
 *
 * Ideal for systems or APIs that expect XML output, or for logging purposes
 * in integrations where JSON or HTML is not suitable.
 *
 * @package    MaplePHP\Blunder\Handlers
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use SimpleXMLElement;
use Throwable;

final class XmlHandler extends AbstractHandler implements HandlerInterface
{
    protected static bool $enabledTraceLines = true;

    /**
     * Exception handler output
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $exceptionItem = new ExceptionItem($exception);
        $trace = $exceptionItem->getTrace($this->getMaxTraceLevel());
        $exceptionItem = new ExceptionItem($exception);

        $xml = new SimpleXMLElement('<xml/>');
        $xml->addChild('status', $exceptionItem->getStatus());
        $xml->addChild('message', $exception->getMessage());
        $xml->addChild('flag', $exceptionItem->getSeverity());
        $xml->addChild('file', $exception->getFile());
        $xml->addChild('line', (string)$exception->getLine());
        $xml->addChild('code', (string)$exception->getCode());

        $xmlTrace = $xml->addChild('trace');
        if ($xmlTrace !== null) {
            foreach ($trace as $row) {
                if (is_array($row)) {
                    $xmlTrace->addChild("file", (string)($row['file'] ?? ""));
                    $xmlTrace->addChild("line", (string)($row['line'] ?? ""));
                    $xmlTrace->addChild("class", (string)($row['class'] ?? ""));
                    $xmlTrace->addChild("function", (string)($row['function'] ?? ""));
                }
            }
        }
        $xmlOutput = (string)$xml->asXML();
        $this->getHttp()->response()->withHeader('content-type', 'application/xml; charset=utf-8');
        $this->getHttp()->response()->getBody()->write($xmlOutput);
        $this->emitter($exceptionItem);
    }
}
