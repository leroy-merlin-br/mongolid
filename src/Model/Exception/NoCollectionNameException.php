<?php
namespace Mongolid\Model\Exception;

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
    protected $message = 'Collection name not specified into Model instance';
}
