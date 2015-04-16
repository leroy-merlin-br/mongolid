<?php

include 'vendor/autoload.php';

if ( ! extension_loaded('mongo')) {
    throw new Exception("MongoClient PHP extension required.", 1);
}

use Mongolid\Mongolid\Container\Ioc;
use Illuminate\Container\Container;

Ioc::setContainer(new Container);
