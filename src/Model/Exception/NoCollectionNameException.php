<?php

namespace Mongolid\Model\Exception;

use Exception;

class NoCollectionNameException extends Exception
{
    /**
     * Exception message.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $message = 'Collection name not specified into Model instance';
}
