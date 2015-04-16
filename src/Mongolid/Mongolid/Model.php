<?php
namespace Mongolid\Mongolid;

use Mongolid\Mongolid\Container\Ioc;

class Model
{
    /**
     * Indicate if the object is new or already persisted.
     * @var boolean
     */
    public $exists = false;

    /**
     * Current attributes.
     * @var array
     */
    public $attributes = [];

    /**
     * Performs save action to persist into database.
     *
     * @return boolean
     */
    public function save()
    {
        $query = $this->newQueryBuilder();

        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        if (! $this->exists) {
            $saved = $query->save($this);
        } else {
            $saved = $query->update($this);
        }

        if ($saved) {
            $this->finishSave();
        }

        return $saved;
    }

    /**
     * This method will can be overwritten in order to fire events to the
     * application. This gives an opportunities to implement the observer design
     * pattern.
     *
     * @param  string $eventName
     * @param  bool   $halt
     * @return mixed
     */
    public function fireModelEvent($eventName, $halt = true)
    {
        return true;
    }

    /**
     * Finishes save() method execution.
     * @return null
     */
    public function finishSave()
    {
        $this->fireModelEvent('saved');

        $this->syncOriginal();
    }

    /**
     * Overwrites the current attributes as original
     * attributes retrieved at MongoDB.
     * @return null
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;
    }

    /**
     * Performs a update operation.
     * @return boolean
     */
    public function update()
    {
        $query = $this->newQueryBuilder();

        if (! $this->exists) {
            return $this->save();
        }

        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        return $query->update($this);
    }

    /**
     * Performs a insert operation into MongoDB.
     * @return boolean
     */
    public function insert()
    {
        $query = $this->newQueryBuilder();

        if (
            $this->fireModelEvent('saving')   === false ||
            $this->fireModelEvent('creating') === false
        ) {
            return false;
        }

        $result = $query->insert($this);

        if ($result) {
            $this->fireModelEvent('saved', false);
            $this->fireModelEvent('created', false);
        }

        return $result;
    }

    /**
     * Performs a delete operation into MongoDB.
     * @return boolean
     */
    public function delete()
    {
        $query = $this->newQueryBuilder();

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        $result = $query->delete($this);

        if ($result) {
            $this->fireModelEvent('deleted', false);
        }

        return $result;
    }

    // public function prepareTimestamps()
    // {
    //     # code...
    // }

    // public function changedAttributes()
    // {
    //     # code...
    // }

    // public function referencesOne()
    // {
    //     # code...
    // }

    // public function referencesMany()
    // {
    //     # code...
    // }

    // public function embedsOne()
    // {
    //     # code...
    // }

    // public function embedsMany()
    // {
    //     # code...
    // }

    // public function attach()
    // {
    //     # code...
    // }

    // public function detach()
    // {
    //     # code...
    // }

    // public function embed()
    // {
    //     # code...
    // }

    // public function unembed()
    // {
    //     # code...
    // }

    // public function polymorph()
    // {
    //     # code...
    // }

    // public function newInstance()
    // {
    //     # code...
    // }

    // public function getAttribute()
    // {
    //     # code...
    // }

    // public function getAttributes()
    // {
    //     # code...
    // }

    // public function getMongoId()
    // {
    //     # code...
    // }

    // public function getCollectionName()
    // {
    //     # code...
    // }

    // public function setAttributes()
    // {
    //     # code...
    // }

    // public function fill()
    // {
    //     # code...
    // }

    // public function toJson()
    // {
    //     # code...
    // }

    // public function toArray()
    // {
    //     # code...
    // }

    // public function __get()
    // {
    //     # code...
    // }

    // public function __set()
    // {
    //     # code...
    // }

    // public function __isset()
    // {
    //     # code...
    // }

    // public function __unset()
    // {
    //     # code...
    // }

    // public function __toString()
    // {
    //     # code...
    // }

    // public function cleanAttribute()
    // {
    //     # code...
    // }

    public function newQueryBuilder()
    {
        return Ioc::make('Mongolid\Mongolid\Query\Builder');
    }

    public function __call($method, $parameters)
    {
        $query = $this->newQueryBuilder();

        return call_user_func_array([$query, $method], $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$query, $method], $parameters);
    }
}
