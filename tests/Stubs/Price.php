<?php
namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;
use Mongolid\Model\Relations\ReferencesOne;

class Price extends AbstractModel
{
    /**
     * @var string
     */
    protected $collection = 'prices';
}
