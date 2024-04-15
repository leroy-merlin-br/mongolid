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
     */
    public function limit(int $amount): static;

    /**
     * Sorts the results by given fields.
     *
     * @param array $fields An array of fields by which to sort.
     *                      Each element in the array has as key the field name,
     *                      and as value either 1 for ascending sort, or -1 for descending sort.
     */
    public function sort(array $fields): static;

    /**
     * Skips a number of results.
     *
     * @param int $amount the number of results to skip
     */
    public function skip(int $amount): static;

    /**
     * Returns the first element of the cursor.
     */
    public function first(): mixed;

    /**
     * Convert the cursor instance to an array of Items.
     */
    public function all(): array;

    /**
     * Return the raw cursor items.
     */
    public function toArray(): array;
}
