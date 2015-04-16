<?php
namespace Mongolid\Mongolid;

use Mongolid\Mongolid\Query\Builder as QueryBuilder;

abstract class Model
{
    public function save()
    {
        # code...
    }

    public function update()
    {
        # code...
    }

    public function insert()
    {
        # code...
    }

    public function delete()
    {
        # code...
    }

    public function prepareTimestamps()
    {
        # code...
    }

    public function changedAttributes()
    {
        # code...
    }

    public function referencesOne()
    {
        # code...
    }

    public function referencesMany()
    {
        # code...
    }

    public function embedsOne()
    {
        # code...
    }

    public function embedsMany()
    {
        # code...
    }

    public function attach()
    {
        # code...
    }

    public function detach()
    {
        # code...
    }

    public function embed()
    {
        # code...
    }

    public function unembed()
    {
        # code...
    }

    public function polymorph()
    {
        # code...
    }

    public function newInstance()
    {
        # code...
    }

    public function getAttribute()
    {
        # code...
    }

    public function getAttributes()
    {
        # code...
    }

    public function getMongoId()
    {
        # code...
    }

    public function getCollectionName()
    {
        # code...
    }

    public function setAttributes()
    {
        # code...
    }

    public function fill()
    {
        # code...
    }

    public function toJson()
    {
        # code...
    }

    public function toArray()
    {
        # code...
    }

    public function __get()
    {
        # code...
    }

    public function __set()
    {
        # code...
    }

    public function __isset()
    {
        # code...
    }

    public function __unset()
    {
        # code...
    }

    public function __toString()
    {
        # code...
    }

    public function cleanAttribute()
    {
        # code...
    }

    public function newQueryBuilder()
    {
        return new QueryBuilder($conn);
    }

    public function __call($method, $parameters)
    {
        $query = $this->newQueryBuilder();

        return call_user_func_array([$query, $method], $parameters);
    }

    public function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$query, $method], $parameters);
    }
}
