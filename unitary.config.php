<?php

/**
 * Default configs, that exists in MaplePHP Unitary
 */
return [
    // # Custom discovery pattern to avoid the "Inception" problem ;)
    // Custom discovery pattern to prevent conflicts when testing multiple
    // MaplePHP libraries simultaneously. This avoids collisions with the
    // Blunder initialization instance inside Unitary.
    'discoverPattern' => "unit-*.php",
];
