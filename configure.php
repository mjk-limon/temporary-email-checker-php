<?php

use Ilm\TempMailChecker\CacheConfigurator;

require 'vendor/autoload.php';

if (php_sapi_name() === 'cli') {
    $configurator = new CacheConfigurator();

    if (isset($argv[1]) && $argv[1] === 'verify') {
        $configurator->verifyCaches();
    } else {
        $configurator->buildAllCaches();
    }
}
