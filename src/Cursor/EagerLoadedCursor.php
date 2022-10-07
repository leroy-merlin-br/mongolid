<?php

namespace Mongolid\Cursor;


use Iterator;

class EagerLoadedCursor extends SchemaCacheableCursor
{
    protected function getCursor(): Iterator
    {
        $cursor = parent::getCursor();
//        (new EagerLoader())->where(
//            $cursor,
//            $this->params[1]['eagerLoads'] ?? []
//        );

        return $cursor;
    }
}
