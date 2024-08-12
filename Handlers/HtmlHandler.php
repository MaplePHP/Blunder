<?php

/**
 * @Package:    MaplePHP - Error HTML handler library
 * @Author:     Daniel Ronkainen
 * @Licence:    Apache-2.0 license, Copyright © Daniel Ronkainen
                Don't delete this comment, its part of the license.
 */

namespace MaplePHP\Blunder\Handlers;

use ErrorException;
use Throwable;

class HtmlHandler extends AbstractHandler
{
    // Set a custom css or js file path, if starts with forward slash
    const CSS_FILE = 'main.css';
    const JS_FILE = 'main.js';

    /**
     * Exception handler output
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
     * @param Throwable $exception
     * @return string
     * @throws ErrorException
     */
    protected function document(Throwable $exception): string
    {
        $trace = $this->getTrace($exception);
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
                            ' . $this->getSeverityBreadcrumb($exception) . '
                            </div>
                            <h1 class="headline-2">' . $exception->getMessage() . '</h1>
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
                            ' . $this->getRows("POST Request", $this->getHttp()->request()->getParsedBody()) . '
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

    /**
     * This is the visible code block
     * @param array $data
     * @param string $code
     * @param int $index
     * @return string
     */
    protected function getCodeBlock(array $data, string $code, int $index = 0): string
    {
        $functionName = (!empty($data['function']) ? ' (' . $data['function'] . ')' : '');

        return '<div class="code-block vcard-1 border-bottom ' . ($index === 0 ? "show" : "") . '">
            <div class="text-sm color-darkgreen mb-5">' . ($data['class'] ?? "")  . $functionName . '</div>
            <div class="text-sm color-grey">' . $data['file'] . '</div>
            <pre>' . $code . '</pre>
        </div>';
    }

    /**
     * This is a navigation item that will point to code block
     * @param int $index
     * @param int $length
     * @param array $stack
     * @return string
     */
    protected function getNavBlock(int $index, int $length, array $stack): string
    {
        $active = ($index === 0) ? " active" : "";
        $functionName = (!empty($stack['function']) ? ' (' . $stack['function'] . ')' : '');

        return '<a class="block text-sm vcard-3 border-bottom' . $active . '" href="#" data-index="' . $index . '" onclick="return navigateCodeBlock(this)">
            <span class="exception mb-5 flex">' . '<strong class="block pr-5">' . ($length - $index) . '.</strong> <span class="block">' . ($stack['class'] ?? "")  . $functionName . '</span></span>
            <span class="block file excerpt-right">' . ltrim($stack['file'], "/") . ': <strong>' . $stack['line'] . '</strong></span>
        </a>';
    }

    protected function getRows(string $title, ?array $rows): string
    {
        $out = '<section class="mb-30">';
        $out .= '<h3 class="headline-3 mb-10">' . $title . '</h3>';
        if(is_array($rows) && count($rows) > 0) {
            foreach($rows as $key => $value) {
                if(is_array($value)) {
                    $value = implode(", ", $value);
                }
                $out .= '<aside class="list flex text-xl mb-10 text-sm" title="' . $key . '">';
                $out .= '<div class="key color-darkgrey">' . $key . '</div>';
                $out .= '<div class="value color-darkgreen">' . $value . '</div>';
                $out .= '</aside>';
            }
        } else {
            $out .= '<div class="text-sm color-darkgrey">None</div>';
        }
        $out .= '</section>';
        return $out;
    }

    /**
     * This will add the navigation block html structure
     * If you wish to edit the block then you should edit the "getNavBlock" method
     * @param array $trace
     * @return string
     */
    final protected function getTraceNavBlock(array $trace): string
    {
        $output = "";
        $length = count($trace);
        foreach ($trace as $index => $stackPoint) {
            if(isset($stackPoint['file']) && is_file($stackPoint['file'])) {
                $output .= $this->getNavBlock($index, $length, $stackPoint);
            }
        }
        return $output;
    }

    /**
     * This will return the MaplePHP logotype
     * @return string
     */
    final protected function getLogo(): string
    {
        return '<svg class="block" width="52" height="43" viewBox="0 0 160 130" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path d="M32.663 100.32c1.876-10.346 5.272-17.294 10.19-20.842 7.376-5.324 14.523-8.323 20.344-11.26 5.822-2.936 16.91-9.044 19.252-12.971 2.342-3.927 6.627-10.46-1.845-19.22-8.472-8.761-12.203-11.984-12.203-18.7 0-6.714 3.434-7.997 5.06-7.997 1.625 0 7.788 2.208 12.038 5.103 4.25 2.895 11.27 7.654 13.33 15.507 2.059 7.853.873 14.062-.732 17.103-1.604 3.041-4.196 7.123-7.817 10.087-2.414 1.976-6.321 4.562-11.722 7.756 6.402-2.495 11.041-5.08 13.916-7.756 4.312-4.012 8.837-10.09 9.579-15.274.742-5.184 1.914-15.07-5.605-23.124-7.52-8.054-17.7-12.38-19.098-12.939a44.56 44.56 0 0 0-2.914-1.046C77.63 2.012 81.317.497 85.499.204c6.272-.44 16.87-.513 27.579 3.488 10.709 4 24.005 10.962 32.883 22.146 8.879 11.183 15.21 27.088 13.857 42.38-1.353 15.293-3.998 24.359-15.462 38.075-11.464 13.716-30.531 21.023-42.303 23.1-11.773 2.075-31.206-1.513-40.953-5.515-9.747-4.002-15.24-9.49-18.247-12.136a80.448 80.448 0 0 1-5.63-5.45c5.773-2.417 11.545-3.625 17.317-3.625 8.657 0 18.395 1 22.81 1.838 4.415.838 12.217 3.163 16.847 3.163 4.631 0 17.757.181 28.304-5.001 10.547-5.183 20.672-13.617 24.117-24.342 2.296-7.15 3.232-13.252 2.805-18.307-.926 11.148-3.96 19.638-9.1 25.469-7.711 8.746-17.122 14.575-25.04 15.877-7.917 1.303-14.611 2.817-27.73 0-13.117-2.817-29.793-6.797-40.062-4.807-6.845 1.327-11.788 2.581-14.828 3.764Z" fill="#48B585"/><g fill="#297252"><path d="M40.756 51.509c3.065 5.201 5.74 8.46 8.027 9.776 3.429 1.973 8.393 2.898 11.567 1.945 2.116-.636 3.761-1.95 4.936-3.942-6.022-.562-10.277-1.3-12.764-2.216-2.488-.916-4.414-1.738-5.779-2.467 4.704 1.32 8.147 2.142 10.331 2.467 2.184.324 4.921.324 8.212 0 .214-1.82-.647-3.486-2.584-5.001-2.906-2.273-5.95-2.01-7.935-2.01-1.984 0-7.825 1.448-9.26 1.448h-4.75ZM2.58 57.666c3.247 7.082 6.355 11.709 9.324 13.88 4.453 3.257 11.313 5.627 16.028 5.267 3.143-.24 5.762-1.42 7.857-3.543-8.44-2.195-14.33-4.16-17.671-5.897-3.34-1.736-5.903-3.218-7.688-4.445 6.404 2.783 11.124 4.646 14.16 5.59 3.034.942 6.925 1.63 11.672 2.063.693-2.153-.176-4.392-2.606-6.718-3.646-3.488-8.03-3.934-10.85-4.433-2.82-.498-11.433-.21-13.472-.57L2.58 57.666ZM7.873 24.18c.068 6.806.848 11.497 2.34 14.072 2.236 3.862 6.56 7.569 10.192 8.431 2.422.575 4.765.222 7.03-1.058-5.56-3.95-9.295-7.074-11.208-9.372-1.913-2.298-3.33-4.188-4.252-5.67 3.847 3.944 6.745 6.691 8.694 8.241 1.948 1.55 4.619 3.097 8.012 4.639 1.23-1.654 1.326-3.766.287-6.339-1.558-3.858-4.676-5.321-6.612-6.442-1.936-1.12-8.448-3.008-9.848-3.818l-4.635-2.683ZM26.102 19.874c8.15-3.043 14.103-4.261 17.86-3.655 5.634.91 12.04 4.394 14.737 8.355 1.798 2.64 2.453 5.612 1.966 8.918-7.264-4.855-12.706-7.902-16.326-9.14-3.62-1.24-6.525-2.072-8.716-2.497 6.471 2.803 11.079 5.018 13.823 6.643 2.743 1.625 5.814 4.118 9.211 7.48-1.407 2.235-3.883 3.32-7.429 3.255-5.318-.097-8.495-3.165-10.72-4.972-2.226-1.808-7.467-8.752-9.077-10.06l-5.329-4.327Z"/></g></g></svg>';
    }

}
