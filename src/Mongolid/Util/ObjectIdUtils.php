<?php
namespace Mongolid\Util;

/**
 * An utility class (aka helper class) related to MongoDB's ObjectId. An
 * "structure" that has only static methods and encapsulates no state.
 */
class ObjectIdUtils
{
    /**
     * Checks if the given value can be a valid ObjectId.
     *
     * @param  string $value String to be evaluated if it can be used as a valid ObjectID.
     *
     * @return boolean       True if is valid.
     */
    public static function isObjectId(string $value)
    {
        if (strlen($value) == 24 && ctype_xdigit($value)) {
            return true;
        }

        return false;
    }
}
