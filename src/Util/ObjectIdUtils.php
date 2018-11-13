<?php
namespace Mongolid\Util;

use MongoDB\BSON\ObjectId;

/**
 * An utility class (aka helper class) related to MongoDB's ObjectId. An
 * "structure" that has only static methods and encapsulates no state.
 */
class ObjectIdUtils
{
    /**
     * Checks if the given value can be a valid ObjectId.
     *
     * @param mixed $value string to be evaluated if it can be used as a valid ObjectId
     */
    public static function isObjectId($value): bool
    {
        if ($value instanceof ObjectId) {
            return true;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        return is_string($value) && 24 == strlen($value) && ctype_xdigit($value);
    }
}
