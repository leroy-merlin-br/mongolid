<?php

namespace Mongolid\Cursor;

use Iterator;
use Countable;

/**
 * Common interface for all kinds of cursors.
 */
interface CursorInterface extends Countable, Iterator
{
    /**
     * Limits the number of results returned.
     *
     * @param int $amount the number of results to return
     *
     * @return CursorInterface returns this cursor
     */
    public function limit(int $amount);

    /**
     * Sorts the results by given fields.
     *
     * @param array $fields An array of fields by which to sort.
     *                      Each element in the array has as key the field name,
     *                      and as value either 1 for ascending sort, or -1 for descending sort.
     *
     * @return CursorInterface returns this cursor
     */
    public function sort(array $fields);

    /**
     * Skips a number of results.
     *
     * @param int $amount the number of results to skip
     *
     * @return CursorInterface returns this cursor
     */
    public function skip(int $amount);

    /**
     * Returns the first element of the cursor.
     *
     * @return mixed
     */
    public function first();

    /**
     * Convert the cursor instance to an array of Items.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Return the raw cursor items.
     *
     * @return array
     */
    public function toArray(): array;
}
