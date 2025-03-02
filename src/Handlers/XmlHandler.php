<?php

/**
 * @Package:    MaplePHP - Error XML handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use SimpleXMLElement;
use Throwable;

class XmlHandler extends AbstractAbstractHandler implements HandlerInterface
{
    protected static bool $enabledTraceLines = true;

    /**
     * Exception handler output
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $trace = $this->getTrace($exception);
        $exceptionItem = new ExceptionItem($exception);

        $xml = new SimpleXMLElement('<xml/>');
        $xml->addChild('status', $exceptionItem->getStatus());
        $xml->addChild('status', $exception->getMessage());
        $xml->addChild('flag', $exceptionItem->getSeverity());
        $xml->addChild('file', $exception->getFile());
        $xml->addChild('line', (string)$exception->getLine());
        $xml->addChild('code', (string)$exception->getCode());

        $xmlTrace = $xml->addChild('trace');
        if(!is_null($xmlTrace)) {
            foreach($trace as $row) {
                if(is_array($row)) {
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
        $this->emitter($exception, $exceptionItem);
    }

    /**
     * This is the visible code block
     * @param array $data
     * @param string $code
     * @param int $index
     * @return string
     */
    protected function getCodeBlock(array $data, string $code, int $index = 0): string
    {
        return $code;
    }
}
