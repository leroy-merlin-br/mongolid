## Troubleshooting

**"PHP Fatal error: Class 'MongoDB\Client' not found in ..."**

The `MongoDB\Client` class is contained in the [**new** MongoDB driver](http://pecl.php.net/package/mongodb) for PHP. [Here is an installation guide](http://www.php.net/manual/en/mongodb.installation.php). The driver is a PHP extension written in C and maintained by [MongoDB](https://mongodb.com). Mongolid and most other MongoDB PHP libraries utilize it in order to be fast and reliable.

**"Class 'MongoDB\Client' not found in ..." in CLI persists even with MongoDB driver installed.**

Make sure that the **php.ini** file used in the CLI environment includes the MongoDB extension. In some systems, the default PHP installation uses different **.ini** files for the web and CLI environments.

Run `php -i | grep 'Configuration File'` in a terminal to check the **.ini** that is being used.

To check if PHP in the CLI environment is importing the driver properly run `php -i | grep 'mongo'` in your terminal. You should get output similar to:

```
$ php -i | grep 'mongo'
mongodb support => enabled
mongodb version => 1.1.3
```

**"This package requires php >=7.0 but your PHP version (X.X.X) does not satisfy that requirement."**

The new (and improved) version 2.0 of Mongolid requires php7. If you are looking for the old PHP 5.x version, head to the [v0.8 branch](https://github.com/leroy-merlin-br/mongolid/tree/v0.8-dev).
