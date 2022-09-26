<?php
namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;
use Mongolid\Model\Relations\ReferencesOne;

class Product extends AbstractModel
{
    /**
     * @var string
     */
    protected $collection = 'products';

    public function price(): ReferencesOne
    {
        return $this->referencesOne(Price::class);
    }
}
