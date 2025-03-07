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

$unit = new Unit();

$unit->case("MaplePHP Blunder redirect test", function ($inst) {

    // SilentHandler will hide the error that I have added in this file
    // and is using to test the Blunder library
    $run = new Run(new SilentHandler());
    $run->severity()
        ->excludeSeverityLevels([E_WARNING, E_USER_WARNING])
        ->redirectTo(function ($errNo, $errStr, $errFile, $errLine) use ($inst) {
            $inst->add($errNo, [
                'equal' => 2
            ]);

            $inst->add($errStr, [
                'equal' => 'Undefined variable $helloWorld'
            ]);

            $inst->add(basename($errFile), [
                'equal' => 'unitary-blunder-redirect.php'
            ]);

            $inst->add(basename($errLine), [
                'isInt' => true
            ]);
        });
    $run->load();
    echo $helloWorld;
});

$unit->execute();
