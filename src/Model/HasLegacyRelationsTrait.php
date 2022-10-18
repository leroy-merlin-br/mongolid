<?php
namespace Mongolid\Model;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Cursor\CursorFactory;
use Mongolid\Cursor\CursorInterface;
use Mongolid\DataMapper\DataMapper;
use Mongolid\LegacyRecord;
use Mongolid\Model\Exception\NotARelationException;
use Mongolid\Model\Relations\RelationInterface;
use Mongolid\Query\EagerLoader\CacheKeyGeneratorTrait;
use Mongolid\Schema\Schema;
use Mongolid\Util\CacheComponentInterface;
use Mongolid\Util\ObjectIdUtils;
use MongolidLaravel\MongolidModel;

/**
 * It is supposed to be used on model classes in general.
 */
trait HasLegacyRelationsTrait
{
    use CacheKeyGeneratorTrait;

    /**
     * Returns the referenced documents as objects.
     *
     * @param string $entity class of the entity or of the schema of the entity
     * @param string $field the field where the _id is stored
     * @param bool $cacheable retrieves a CacheableCursor instead
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    protected function referencesOne(string $entity, string $field, bool $cacheable = true)
    {
        $referencedId = $this->$field;

        if (is_array($referencedId) && isset($referencedId[0])) {
            $referencedId = $referencedId[0];
        }

        $entityInstance = Container::make($entity);

        if ($cacheable && $referencedId && $document = $this->getDocumentFromCache($entityInstance, $referencedId)) {
            return $document;
        }

        if ($entityInstance instanceof Schema) {
            $dataMapper = Container::make(DataMapper::class);
            $dataMapper->setSchema($entityInstance);

            return $dataMapper->first(['_id' => $referencedId], [], $cacheable);
        }

        return $entityInstance::first(['_id' => $referencedId], [], $cacheable);
    }

    /**
     * Returns the cursor for the referenced documents as objects.
     *
     * @param string $entity    class of the entity or of the schema of the entity
     * @param string $field     the field where the _ids are stored
     * @param bool   $cacheable retrieves a CacheableCursor instead
     *
     * @return array
     */
    protected function referencesMany(string $entity, string $field, bool $cacheable = true)
    {
        $referencedIds = (array) $this->$field;

        if (ObjectIdUtils::isObjectId($referencedIds[0] ?? '')) {
            foreach ($referencedIds as $key => $value) {
                $referencedIds[$key] = new ObjectId($value);
            }
        }

        $query = ['_id' => ['$in' => array_values($referencedIds)]];

        $entityInstance = Container::make($entity);

        if ($entityInstance instanceof Schema) {
            $dataMapper = Container::make(DataMapper::class);
            $dataMapper->setSchema($entityInstance);

            return $dataMapper->where($query, [], $cacheable);
        }

        return $entityInstance::where($query, [], $cacheable);
    }

    /**
     * Return a embedded documents as object.
     *
     * @param string $entity class of the entity or of the schema of the entity
     * @param string $field  field where the embedded document is stored
     *
     * @return LegacyRecord|Schema|null
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

        return Container::make(CursorFactory::class)
            ->createEmbeddedCursor($entity, $items)->first();
    }

    /**
     * Return array of embedded documents as objects.
     *
     * @param string $entity class of the entity or of the schema of the entity
     * @param string $field  field where the embedded documents are stored
     *
     * @return CursorInterface Array with the embedded documents
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

        return Container::make(CursorFactory::class)
            ->createEmbeddedCursor($entity, $items);
    }

    /**
     * Embed a new document to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field field to where the $obj will be embedded
     * @param mixed  $obj   document or model instance
     */
    public function embed(string $field, &$obj)
    {
        $embedder = Container::make(DocumentEmbedder::class);
        $embedder->embed($this, $field, $obj);
    }

    /**
     * Removes an embedded document from the given field. It does that by using
     * the _id of the given $obj.
     *
     * @param string $field name of the field where the $obj is embeded
     * @param mixed  $obj   document, model instance or _id
     */
    public function unembed(string $field, &$obj)
    {
        $embedder = Container::make(DocumentEmbedder::class);
        $embedder->unembed($this, $field, $obj);
    }

    /**
     * Attach document _id reference to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field name of the field where the reference will be stored
     * @param mixed  $obj   document, model instance or _id to be referenced
     */
    public function attach(string $field, &$obj)
    {
        $embedder = Container::make(DocumentEmbedder::class);
        $embedder->attach($this, $field, $obj);
    }

    /**
     * Removes a document _id reference from an attribute. It will remove the
     * _id of the given $obj from inside the given $field.
     *
     * @param string $field field where the reference is stored
     * @param mixed  $obj   document, model instance or _id that have been referenced by $field
     */
    public function detach(string $field, &$obj)
    {
        $embedder = Container::make(DocumentEmbedder::class);
        $embedder->detach($this, $field, $obj);
    }

    /**
     * @return mixed|null
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function getDocumentFromCache(ModelInterface $entityInstance, string $referencedId)
    {
        /** @var CacheComponentInterface $cacheComponent */
        $cacheComponent = Container::make(CacheComponentInterface::class);
        $cacheKey = $this->generateCacheKey($entityInstance, $referencedId);

        // Checks if the model was already eager loaded.
        // if so, we don't need to query database to
        // use the document.
        if (!$document = $cacheComponent->get($cacheKey)) {
            return null;
        }

        return $document;
    }
}
