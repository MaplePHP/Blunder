<?php

/**
 * @Package:    MaplePHP - Error XML handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use MaplePHP\Blunder\ExceptionItem;
use SimpleXMLElement;
use Throwable;

class XmlHandler extends AbstractHandler
{
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
        $xml->addChild('line', $exception->getLine());
        $xml->addChild('code', $exception->getCode());

        $xmlTrace = $xml->addChild('trace');
        foreach($trace as $row) {
            $xmlTrace->addChild("file", $row['file']);
            $xmlTrace->addChild("line", $row['line']);
            $xmlTrace->addChild("class", $row['class']);
            $xmlTrace->addChild("function", $row['function']);
        }
        $this->getHttp()->response()->withHeader('content-type', 'application/xml; charset=utf-8');
        $this->getHttp()->response()->getBody()->write($xml->asXML());
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
