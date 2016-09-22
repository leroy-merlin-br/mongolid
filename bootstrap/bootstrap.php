<?php

include 'vendor/autoload.php';

if (!extension_loaded('mongodb')) {
    throw new Exception('MongoClient PHP extension required.', 1);
}

use Illuminate\Container\Container;
use Mongolid\Container\Ioc;

Ioc::setContainer(new Container());
