# Mongolid ODM for MongoDB (PHP7)

> Easy, powerful and ultrafast ODM for PHP7 build on top of the [new mongodb driver](https://docs.mongodb.org/ecosystem/drivers/php/).

![Mongolid](https://user-images.githubusercontent.com/1991286/28967747-fe5c258a-78f2-11e7-91c7-8850ffb32004.png)

Mongolid supports both **ActiveRecord** and **DataMapper** patterns. **You choose! (:**

[![Build Status](https://travis-ci.org/leroy-merlin-br/mongolid.svg?branch=master)](https://travis-ci.org/leroy-merlin-br/mongolid)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/cc45e93bb0d0413d9e0355c7377d4d33)](https://www.codacy.com/app/zizaco/mongolid?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=leroy-merlin-br/mongolid&amp;utm_campaign=Badge_Grade)
[![StyleCI](https://styleci.io/repos/9799450/shield?branch=master)](https://styleci.io/repos/9799450)
[![Coverage Status](https://coveralls.io/repos/github/leroy-merlin-br/mongolid/badge.svg?branch=master)](https://coveralls.io/github/leroy-merlin-br/mongolid?branch=master)
[![Latest Stable Version](https://poser.pugx.org/leroy-merlin-br/mongolid/v/stable)](https://packagist.org/packages/leroy-merlin-br/mongolid)
[![Total Downloads](https://poser.pugx.org/leroy-merlin-br/mongolid/downloads)](https://packagist.org/packages/leroy-merlin-br/mongolid)
[![Latest Unstable Version](https://poser.pugx.org/leroy-merlin-br/mongolid/v/unstable)](https://packagist.org/packages/leroy-merlin-br/mongolid)
[![License](https://poser.pugx.org/leroy-merlin-br/mongolid/license)](https://packagist.org/packages/leroy-merlin-br/mongolid)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/25636a94-9a5d-4438-bd5e-9f9694104529/small.png)](https://insight.sensiolabs.com/projects/25636a94-9a5d-4438-bd5e-9f9694104529)

<a name="introduction"></a>
## Introduction

Mongolid ODM (Object Document Mapper) provides a beautiful, simple implementation for working with MongoDB. Each database collection can have a corresponding "Model" which is used to interact with that collection.

> **Note:** If you are working with Laravel, take a look at [mongolid-laravel repository](https://github.com/leroy-merlin-br/mongolid-laravel).

<a name="installation"></a>
## Installation

You can install library through Composer:

```
$ composer require leroy-merlin-br/mongolid
```

### Requirements

- PHP**7**
- [MongoDB Driver](http://php.net/manual/en/set.mongodb.php)

> **Note:** If you are looking for the old PHP 5.x version, head to the [v0.8 branch](https://github.com/leroy-merlin-br/mongolid/tree/v0.8-dev).

## [Read the Docs: <small>leroy-merlin-br.github.com/mongolid</small>](http://leroy-merlin-br.github.com/mongolid)
[![Mongolid Docs](https://dl.dropboxusercontent.com/u/12506137/libs_bundles/MongolidDocs.png)](http://leroy-merlin-br.github.com/mongolid)

<a name="troubleshooting"></a>
## Troubleshooting

**"PHP Fatal error: Class 'MongoDB\Client' not found in ..."**

The `MongoDB\Client` class is contained in the [**new** MongoDB driver](http://pecl.php.net/package/mongodb) for PHP. [Here is an installation guide](http://www.php.net/manual/en/mongodb.installation.php). The driver is a PHP extension written in C and maintained by [MongoDB](https://mongodb.com). Mongolid and most other MongoDB PHP libraries utilize it in order to be fast and reliable.

**"Class 'MongoDB\Client' not found in ..." in CLI persists even with MongoDB driver installed.**

Make sure that the **php.ini** file used in the CLI environment includes the MongoDB extension. In some systems, the default PHP installation uses different **.ini** files for the web and CLI environments.

Run `php -i | grep 'Configuration File'` in a terminal to check the **.ini** that is being used.

To check if PHP in the CLI environment is importing the driver properly run `php -i | grep -i 'mongo'` in your terminal. You should get output similar to:

```
$ php -i | grep -i 'mongo'
MongoDB support => enabled
MongoDB extension version => 1.2.8
MongoDB extension stability => stable
libmongoc bundled version => 1.5.5
```

**"This package requires php >=7.0 but your PHP version (X.X.X) does not satisfy that requirement."**

The new (and improved) version 2.0 of Mongolid requires php7. If you are looking for the old PHP 5.x version, head to the [v0.8 branch](https://github.com/leroy-merlin-br/mongolid/tree/v0.8-dev).

<a name="license"></a>
## License

Mongolid is free software distributed under the terms of the [MIT license](http://opensource.org/licenses/MIT)

<a name="additional_information"></a>
## Additional information

Mongolid was proudly built by the [Leroy Merlin Brazil](https://github.com/leroy-merlin-br) team. [See all the contributors](https://github.com/leroy-merlin-br/mongolid/graphs/contributors).

Any questions, feel free to contact us.

Any issues, please [report here](https://github.com/Zizaco/mongolid)
