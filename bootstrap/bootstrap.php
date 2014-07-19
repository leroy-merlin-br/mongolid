<?php

include 'vendor/autoload.php';

// Verify if mongo.so extension was loaded.
if ( ! extension_loaded('mongo')) {
    echo 'MongoClient PHP extension required.'.PHP_EOL;

    exit(1);
}

use Mongolid\Mongolid\Container\IOC;
use Illuminate\Container\Container;

if (! class_exists('IOC')) {
    class_alias('Mongolid\Mongolid\Container\IOC', 'IOC');
}

$container = new Container;

// Binding for all required classes
$requiredResources = array(
    'Connection'      => '\Mongolid\Mongolid\Connection\Connection',
    'Model'           => '\Mongolid\Mongolid\Model',
    'MongoClient'     => 'MongoClient',
    'MongoDB'         => 'MongoDB',
    'MongoCollection' => 'MongoCollection',
    'MongoCursor'     => 'MongoCursor'
);

$container->bind($requiredResources);

IOC::setContainer($container);
