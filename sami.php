<?php

/**
 * Sami configuration file.
 *
 * @see https://github.com/FriendsOfPHP/Sami
 */

return new Sami\Sami(
    './src',
    [
        'title' => 'Mongolid ODM Api',
        'build_dir' => __DIR__.'/site/api',
        'cache_dir' => __DIR__.'/build/cache',
    ]
);
