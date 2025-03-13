#!/usr/bin/env php
<?php
/**
 * This is how a template test file should look like but
 * when used in MaplePHP framework you can skip the "bash code" at top and the "autoload file"!
 */

use MaplePHP\Blunder\ExceptionItem;
use MaplePHP\Blunder\Handlers\CliHandler;
use MaplePHP\Blunder\Handlers\HtmlHandler;
use MaplePHP\Blunder\Handlers\JsonHandler;
use MaplePHP\Blunder\Handlers\PlainTextHandler;
use MaplePHP\Blunder\Handlers\SilentHandler;
use MaplePHP\Blunder\Handlers\TextHandler;
use MaplePHP\Blunder\Handlers\XmlHandler;
use MaplePHP\Blunder\Interfaces\HandlerInterface;
use MaplePHP\Blunder\Run;
use MaplePHP\Unitary\TestWrapper;
use MaplePHP\Unitary\Unit;

// If you add true to Unit it will run in quite mode
// and only report if it finds any errors!


$unit = new Unit();

$unit->case("MaplePHP Blunder handler test", function ($inst) {

    // SilentHandler will hide the error that I have added in this file
    // and is using to test the Blunder library

    $run = new Run(new SilentHandler());
    $run->severity()
        ->excludeSeverityLevels([E_WARNING, E_USER_WARNING])
        ->redirectTo(function ($errNo, $errStr, $errFile, $errLine) use ($inst) {

            $func = function (string $className) {
                $dispatch = $this->wrapper($className)->bind(function ($exception) {
                    $this->setExitCode(null);
                    ob_start();
                    $this->exceptionHandler($exception);
                    return ob_get_clean();
                });
                return $dispatch(new Exception("Mock exception"));
            };;

            $inst->add($func(HtmlHandler::class), [
                'length' => 100,
                'isFullHtml' => true
            ], "HtmlHandler do not return a valid HTML string");

            $inst->add($func(JsonHandler::class), [
                'length' => 100,
                'isJson' => true
            ], "JsonHandler do not return a valid JSON string");

            $inst->add($func(TextHandler::class), [
                'length' => [10],
            ], "TextHandler do not return a valid CLI string");

            $inst->add($func(PlainTextHandler::class), [
                'length' => [10],
            ], "PlainTextHandler do not return a valid CLI string");

            $inst->add($func(XmlHandler::class), [
                'length' => [10],
            ], "CliHandler do not return a valid CLI string");

            $inst->add($func(CliHandler::class), [
                'length' => [1],
            ], "CliHandler do not return a valid CLI string");

            return true;
        });
    $run->load();

    // Mock error
    echo $helloWorld;
});

$unit->execute();
