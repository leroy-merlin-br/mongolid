<?php

include 'vendor/autoload.php';

if ( ! extension_loaded('mongodb')) {
    throw new Exception("MongoClient PHP extension required.", 1);
}

use Mongolid\Container\Ioc;
use Illuminate\Container\Container;

Ioc::setContainer(new Container);
