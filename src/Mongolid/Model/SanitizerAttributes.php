<?php
namespace Mongolid\Model;

class Sanitizer
{
    public function sanitize(Model $instance)
    {
        $attributes = $this->prepareMongoId($instance->getAttributes());

        if ($instance->hasTimestamp()) {
            $attributes = $this->prepareTimestamps($attributes);
        }

        return $attributes;
    }

    public function prepareMongoId(array $attributes)
    {
        if (! isset($attributes['_id'])) {
            return $attributes;
        }

        $_id = $attributes['_id'];

        if ($this->isMongoId($_id)) {
            $attributes['_id'] = new \MongoId($_id);
        } elseif (is_numeric($_id)) {
            $attributes['_id'] = (int)$_id;
        }

        return $attributes;
    }

    public function isMongoId($value)
    {
        return MongoId::isValid($value);
    }

    public function prepareTimestamps($attributes)
    {
        if (! array_key_exists('created_at', $attributes)) {
            $attributes['created_at'] = new MongoDate;
        }

        $attributes['updated_at'] = new MongoDate;

        return $attributes;
    }
}
