<?php

namespace Mongolid\Exception;

use Exception;

/**
 * Class NoCollectionNameException.
 */
class NoCollectionNameException extends Exception
{
    /**
     * Exception message.
     *
     * @var string
     */
    protected $message = 'Collection name not especified into ActiveRecord instance';
}
