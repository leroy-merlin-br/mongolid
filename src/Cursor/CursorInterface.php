<?php
namespace Mongolid\Cursor;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Iterator;

/**
 * Common interface for all kinds of cursors.
 */
interface CursorInterface extends Countable, Iterator, Arrayable
{
    /**
     * Limits the number of results returned.
     *
     * @param int $amount the number of results to return
     *
     * @return static
     */
    public function limit(int $amount): CursorInterface;

    /**
     * Sorts the results by given fields.
     *
     * @param array $fields An array of fields by which to sort.
     *                      Each element in the array has as key the field name,
     *                      and as value either 1 for ascending sort, or -1 for descending sort.
     *
     * @return static
     */
    public function sort(array $fields): CursorInterface;

    /**
     * Skips a number of results.
     *
     * @param int $amount the number of results to skip
     *
     * @return static
     */
    public function skip(int $amount): CursorInterface;

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
