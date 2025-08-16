#!/usr/bin/env php
<?php
/**
 * This is how a template test file should look like but
 * when used in MaplePHP framework you can skip the "bash code" at top and the "autoload file"!
 */

use MaplePHP\Blunder\Handlers\SilentHandler;
use MaplePHP\Blunder\Run;
use MaplePHP\Unitary\Unit;

// If you add true to Unit it will run in quite mode
// and only report if it finds any errors!


group("MaplePHP Blunder event test", function ($inst) {

    // SilentHandler will hide the error that I have added in this file
    // and is using to test the Blunder library
    $run = new Run(new SilentHandler());
    $run->event(function ($item, $http) use ($inst) {
        $inst->add($item->getStatus(), function () {
            return $this->equal("warning");

        }, "getStatus is not equal to warning");

        $inst->add($item->getSeverity(), [
            "equal" => ["E_WARNING"],
        ], "getSeverity is not a equal to E_WARNING");

        $inst->add($item->getLine(), [
            "isInt" => [],
            "length" => [1],
        ], "getLine is not a int");

        $inst->add($item->getMask(), [
            "isInt" => [],
            "length" => [1],
        ], "getMask is not a int");

        $inst->add($item->getStatus(), [
            "isString" => [],
            "length" => [1],
        ], "getFile is not a file: ". $item->getFile());

        $inst->add($item->isLevelFatal(), [
            "isBool" => [],
            "equal" => [false]
        ], "isLevelFatal is not a false");
    });

    $run->load();
    echo $helloworld;
});
