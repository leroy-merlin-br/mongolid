<?php

namespace Mongolid\Model;

use MongoDB\BSON\ObjectID;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\CursorFactory;
use Mongolid\Cursor\EmbeddedCursor;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Schema;
use Mongolid\Util\ObjectIdUtils;

/**
 * It is supposed to be used in model classes in general.
 */
trait Relations
{
    /**
     * Returns the referenced documents as objects.
     *
     * @param string $entity Class of the entity or of the schema of the entity.
     * @param string $field  The field where the _id is stored.
     *
     * @return mixed
     */
    protected function referencesOne(string $entity, string $field)
    {
        $referenced_id = $this->$field;

        if (is_array($referenced_id) && isset($referenced_id[0])) {
            $referenced_id = $referenced_id[0];
        }

        $entityInstance = Ioc::make($entity);

        if ($entityInstance instanceof Schema) {
            $dataMapper = Ioc::make(DataMapper::class);
            $dataMapper->schema = $entityInstance;

            return $dataMapper->first(['_id' => $referenced_id]);
        }

        return $entityInstance::first(['_id' => $referenced_id]);
    }

    /**
     * Returns the cursor for the referenced documents as objects.
     *
     * @param string $entity Class of the entity or of the schema of the entity.
     * @param string $field  The field where the _ids are stored.
     *
     * @return array
     */
    protected function referencesMany(string $entity, string $field)
    {
        $referencedIds = (array) $this->$field;

        if (ObjectIdUtils::isObjectId($referencedIds[0] ?? '')) {
            foreach ($referencedIds as $key => $value) {
                $referencedIds[$key] = new ObjectID($value);
            }
        }

        $query = ['_id' => ['$in' => array_values($referencedIds)]];

        $entityInstance = Ioc::make($entity);

        if ($entityInstance instanceof Schema) {
            $dataMapper = Ioc::make(DataMapper::class);
            $dataMapper->schema = $entityInstance;

            return $dataMapper->where($query);
        }

        return $entityInstance::where($query);
    }

    /**
     * Return a embedded documents as object.
     *
     * @param string $entity Class of the entity or of the schema of the entity.
     * @param string $field  Field where the embedded document is stored.
     *
     * @return Model|null
     */
    protected function embedsOne(string $entity, string $field)
    {
        if (is_subclass_of($entity, Schema::class)) {
            $entity = (new $entity())->entityClass;
        }

        $items = (array) $this->$field;
        if (false === empty($items) && false === array_key_exists(0, $items)) {
            $items = [$items];
        }

        return Ioc::make(CursorFactory::class)
            ->createEmbeddedCursor($entity, $items)->first();
    }

    /**
     * Return array of embedded documents as objects.
     *
     * @param string $entity Class of the entity or of the schema of the entity.
     * @param string $field  Field where the embedded documents are stored.
     *
     * @return EmbeddedCursor Array with the embedded documents
     */
    protected function embedsMany(string $entity, string $field)
    {
        if (is_subclass_of($entity, Schema::class)) {
            $entity = (new $entity())->entityClass;
        }

        $items = (array) $this->$field;
        if (false === empty($items) && false === array_key_exists(0, $items)) {
            $items = [$items];
        }

        return Ioc::make(CursorFactory::class)
            ->createEmbeddedCursor($entity, $items);
    }

    /**
     * Embed a new document to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field Field to where the $obj will be embedded.
     * @param mixed  $obj   Document or model instance.
     *
     * @return void
     */
    public function embed(string $field, &$obj)
    {
        $embeder = Ioc::make(DocumentEmbedder::class);
        $embeder->embed($this, $field, $obj);
    }

    /**
     * Removes an embedded document from the given field. It does that by using
     * the _id of the given $obj.
     *
     * @param string $field Name of the field where the $obj is embeded.
     * @param mixed  $obj   Document, model instance or _id.
     *
     * @return void
     */
    public function unembed(string $field, &$obj)
    {
        $embeder = Ioc::make(DocumentEmbedder::class);
        $embeder->unembed($this, $field, $obj);
    }

    /**
     * Attach document _id reference to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field Name of the field where the reference will be stored.
     * @param mixed  $obj   Document, model instance or _id to be referenced.
     *
     * @return void
     */
    public function attach(string $field, &$obj)
    {
        $embeder = Ioc::make(DocumentEmbedder::class);
        $embeder->attach($this, $field, $obj);
    }

    /**
     * Removes a document _id reference from an attribute. It will remove the
     * _id of the given $obj from inside the given $field.
     *
     * @param string $field Field where the reference is stored.
     * @param mixed  $obj   Document, model instance or _id that have been referenced by $field.
     *
     * @return void
     */
    public function detach(string $field, &$obj)
    {
        $embeder = Ioc::make(DocumentEmbedder::class);
        $embeder->detach($this, $field, $obj);
    }
}
