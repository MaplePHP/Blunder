<?php

/**
 * @Package:    MaplePHP - Error HTML handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use ErrorException;
use MaplePHP\Blunder\ExceptionMetadata;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use MaplePHP\Blunder\Templates\HtmlHelperTrait;
use Throwable;

class HtmlHandler extends AbstractHandler implements HandlerInterface
{
    use HtmlHelperTrait;

    /** @var string */
    public const CSS_FILE = 'main.css';
    /** @var string */
    public const JS_FILE = 'main.js';

    protected static bool $enabledTraceLines = true;

    /**
     * Exception handler output
     *
     * @param Throwable $exception
     * @return void
     * @throws ErrorException
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $this->getHttp()->response()->getBody()->write($this->document($exception));
        $this->emitter($exception);
    }

    /**
     * The pretty output template
     *
     * @param Throwable $exception
     * @return string
     * @throws ErrorException
     */
    protected function document(Throwable $exception): string
    {
        $meta = new ExceptionMetadata($exception);
        $meta->setMaxTraceLevel(static::$enabledTraceLines);
        $trace = $meta->getTrace();
        $codeBlockArr = $this->getTraceCodeBlock($trace);
        $port = $this->getHttp()->request()->getUri()->getPort();
        if(is_null($port)) {
            $port = 80;
        }

        return '
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>MaplePHP / Blunder</title>
                <style>' . $this->getAssetContent(static::CSS_FILE) . '</style>
                <script>
                    ' . $this->getAssetContent(static::JS_FILE) . '
                </script>
            </head>
            <body>
                <main id="main">
                    <aside id="nav">
                        <header class="vcard-2 bg-white border-bottom flex items-center">
                            <figure class="logo">' . $this->getLogo() . '</figure>
                            <h2 class="headline-2 bold"><span class="color-green">MaplePHP</span><span class="px-4">/</span>Blunder</h2>         
                        </header>
                        <nav>
                            ' . $this->getTraceNavBlock($trace) . '
                        </nav>
                    </aside>
                    <article>
                        <header id="breadcrumb" class="vcard-1 border-bottom">
                            <div>
                            ' . $this->getSeverityBreadcrumb($meta) . '
                            </div>
                            <h1 class="headline-2">' .
                                "<strong>" . $meta->getSeverityTitle() . ":</strong>" . " " .
                                $exception->getMessage() .
                            '</h1>
                            <a id="smart-nav-btn" class="hide" onclick="return openNavigation(this)" href="#"></a>
                        </header>
                        ' . implode("\n", $codeBlockArr) . '
                        <div class="vcard-1">
                            <h2 class="headline-1 color-green pb-15 mb-20 border-bottom">Details</h2>
                            
                            ' . $this->getRows("URI/GET Request", [
                                'Method' => $this->getHttp()->request()->getMethod(),
                                'URI' => $this->getHttp()->request()->getUri()->getUri(),
                                'SSL' => ($this->getHttp()->request()->getUri()->getScheme() === "https") ? "true" : "false",
                                'Port' => $port,
                                'Query' => $this->getHttp()->request()->getUri()->getQuery()
                            ]) . '
                            ' . $this->getRows("POST Request", (array)$this->getHttp()->request()->getParsedBody()) . '
                            ' . $this->getRows("FILE Request", $this->getHttp()->request()->getUploadedFiles()) . '
                            ' . $this->getRows("COOKIE", $this->getHttp()->request()->getCookieParams()) . '
                            ' . $this->getRows("SESSION", ($_SESSION ?? null)) . '
                            ' . $this->getRows("SERVER", $this->getHttp()->request()->getServerParams()) . '
                        </div>
                    </article>
                </main>
            </body>
            </html>
        ';
    }
}
