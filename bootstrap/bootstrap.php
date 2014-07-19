<?php

include 'vendor/autoload.php';

// Verify if mongo.so extension was loaded.
if ( ! extension_loaded('mongo')) {
    throw new Exception("MongoClient PHP extension required.", 1);
}

use Mongolid\Mongolid\Container\Ioc;
use Illuminate\Container\Container;

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

Ioc::setContainer($container);
