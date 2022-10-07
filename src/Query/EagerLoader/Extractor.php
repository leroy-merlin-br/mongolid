<?php

namespace Mongolid\Query\EagerLoader;


use MongoDB\BSON\ObjectId;
use function Mongolid\Cursor\EagerLoader\str_contains;

class Extractor
{
    /**
     * @var array
     */
    private $relatedModels;

    public function __construct(array $relatedModels)
    {
        $this->relatedModels = $relatedModels;
    }

    public function extractFrom(array $model): array
    {
        foreach ($this->relatedModels as $eagerLoadKey => $loadModel) {
            $key = $this->extractKeyFrom($loadModel);
            if ($this->keyHasDots($key)) {
                $this->extractFromEmbeddedModel($eagerLoadKey, $model, $key);

                continue;
            }

            $this->extractFromModel($eagerLoadKey, $model, $key);
        }

        return $this->relatedModels;
    }

    public function getRelatedModels(): array
    {
        return $this->relatedModels;
    }

    private function addIdFor(string $eagerLoadKey, $id): void
    {
        $this->relatedModels[$eagerLoadKey]['ids'][$id] = $id;
    }

    private function keyHasDots($key)
    {
        return str_contains($key, '.');
    }

    public function extractFromEmbeddedModel(string $eagerLoadKey, array $model, string $key): array
    {
        list($method, $attribute) = explode('.', $key);
        foreach ($model[$method] ?? [] as $embeddedModel) {
            $this->extractFromModel($eagerLoadKey, $embeddedModel, $attribute);
        }

        return $ids ?? [];
    }

    private function extractFromModel(string $eagerLoadKey, array $model, string $key): void
    {
        $id = $model[$key];
        if ($id instanceof ObjectId) {
            $id = (string) $id;
        }

        $this->addIdFor($eagerLoadKey, $id);
    }

    private function extractKeyFrom(array $eagerLoadModel): string
    {
        return $eagerLoadModel['key'] ?? '_id';
    }
}
