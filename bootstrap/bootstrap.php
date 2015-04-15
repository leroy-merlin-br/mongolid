<?php

include 'vendor/autoload.php';

if ( ! extension_loaded('mongo')) {
    throw new Exception("MongoClient PHP extension required.", 1);
}

use Mongolid\Mongolid\Container\Ioc;
use Illuminate\Container\Container;

$container = new Container;

$requiredResources = [
    'Connection' => '\Mongolid\Mongolid\Connection\Connection'
];

$container->bind($requiredResources);

Ioc::setContainer($container);
