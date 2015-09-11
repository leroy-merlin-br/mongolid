<?php

use Mockery as m;
use Zizaco\Mongolid\Model;

class ModelTest extends PHPUnit_Framework_TestCase
{
    protected $mongoMock            = null;
    protected $productsCollection   = null;
    protected $categoriesCollection = null;
    protected $eventsCollection     = null;
    protected $cursor               = null;

    public function setUp()
    {
        $this->mongoMock            = m::mock('Connection');
        $this->productsCollection   = m::mock('Collection');
        $this->categoriesCollection = m::mock('Collection');
        $this->cursor               = m::mock(new _stubCursor);

        $this->mongoMock->mongolid        = $this->mongoMock;
        $this->mongoMock->test_products   = $this->productsCollection;
        $this->mongoMock->test_categories = $this->categoriesCollection;

        _stubProduct::$connection          = $this->mongoMock;
        _stubProductPersisted::$connection = $this->mongoMock;
        _stubCategory::$connection         = $this->mongoMock;
    }

    public function tearDown()
    {
        m::close();

        _stubProduct::$connection          = null;
        _stubProductPersisted::$connection = null;
        _stubCategory::$connection         = null;
    }

    public function testShouldInsert()
    {
        $prod       = new _stubProduct;
        $prod->name = 'Something';

        $this->productsCollection
            ->shouldReceive('insert')
            ->with(
                m::any(),
                ['w' => 1]
            )
            ->once()
            ->andReturn(['ok' => 1]);

        $this->assertTrue($prod->insert());
    }

    public function testShouldNotInsert()
    {
        $model =  new Model;
        $this->assertFalse($model->insert());

        $model = m::mock('_stubProduct[fireModelEvent]');
        $model->shouldAllowMockingProtectedMethods();

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('saving')
            ->andReturn(false);

        $this->assertFalse($model->insert());

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('saving')
            ->andReturn(true);

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('creating')
            ->andReturn(false);

        $this->assertFalse($model->insert());

        $model->shouldReceive('fireModelEvent')
            ->twice()
            ->andReturn(true);

        $this->productsCollection
            ->shouldReceive('insert')
            ->once()
            ->andReturn(false);

        $this->assertFalse($model->insert());
    }

    public function testShouldSave()
    {
        $prod       = new _stubProduct;
        $prod->name = 'Something';

        $this->productsCollection
            ->shouldReceive('save')
            ->with(
                m::any(),
                ['w' => 1]
            )
            ->once()
            ->andReturn(['ok' => 1]);

        $this->assertTrue($prod->save());
    }

    public function testShouldNotSave()
    {
        $model =  new Model;
        $this->assertFalse($model->save());

        $model = m::mock('_stubProduct[fireModelEvent]');
        $model->shouldAllowMockingProtectedMethods();

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('saving')
            ->andReturn(false);

        $this->assertFalse($model->save());

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('saving')
            ->andReturn(true);

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('creating')
            ->andReturn(false);

        $this->assertFalse($model->save());

        $model->shouldReceive('fireModelEvent')
            ->twice()
            ->andReturn(true);

        $this->productsCollection
            ->shouldReceive('save')
            ->once()
            ->andReturn(false);

        $this->assertFalse($model->save());
    }

    public function testShouldUpdate()
    {
        $prod       = new _stubProductPersisted;
        $prod->name = 'Something';

        $this->productsCollection
            ->shouldReceive('update')
            ->with(
                ['_id' => $prod->_id],
                ['$set' => ['name' => 'Something', "desc" => "whatever2"]],
                ['w' => 1]
            )
            ->once()
            ->andReturn(['ok' => 1]);

        $this->assertTrue($prod->update());
    }

    /**
     * @group test
     */
    public function testShouldNotUpdate()
    {
        $model =  new Model;
        $this->assertFalse($model->update());

        $model = m::mock('_stubProduct[]');
        $this->assertFalse($model->update());
    }

    public function testShouldHasUpdatedAtAndCreatedAtFields()
    {
        $prod       = new _stubProduct;
        $prod->name = 'Something';

        $prod->prepareTimestamps();

        $this->assertTrue($prod->updated_at instanceOf MongoDate);
        $this->assertTrue($prod->created_at instanceOf MongoDate);
    }

    public function testShouldDelete()
    {
        $prod       = new _stubProduct;
        $prod->name = 'Something';

        $this->productsCollection
            ->shouldReceive('remove')
            ->with(
                $this->prepareMongoAttributes($prod->attributes)
            )
            ->once()
            ->andReturn(['ok' => 1]);

        $this->assertTrue($prod->delete());
    }

    public function testShouldCastToString()
    {
        $model = new Model;

        $this->assertEquals(
            $model->toJson(),
            (string) $model
        );
    }

    public function testShouldGetAttribute()
    {
        $model = new Model;

        $model->randomAttribute = rand();

        $this->assertTrue(
            isset($model->randomAttribute)
        );
    }

    public function testShouldNotCallInvalid()
    {
        $this->setExpectedException('Exception', 'Call to undefined method invalid__', 1);

        $model = new Model;
        $model->invalid__();
    }

    public function testShouldFindFirst()
    {
        $existentProduct = [
            '_id'   => new MongoId,
            'name'  => 'Bacon',
            'price' => 10.50,
        ];

        $query = ['name' => 'Bacon'];

        $this->productsCollection
            ->shouldReceive('findOne')
            ->with(
                $query, []
            )
            ->once()
            ->andReturn(
                $existentProduct
            );

        $result = _stubProduct::first($query);
        $this->assertEquals($existentProduct, $result->toArray());

        // With fields parameter
        unset($existentProduct['name']);

        $this->productsCollection
            ->shouldReceive('findOne')
            ->with(
                $query, ['price' => 1]
            )
            ->once()
            ->andReturn(
                $existentProduct
            );

        $result = _stubProduct::first($query, ['price']);
        $this->assertEquals($existentProduct, $result->toArray());
    }

    public function testShouldFind()
    {
        $existentProduct = [
            '_id'   => new MongoId,
            'name'  => 'Bacon',
            'price' => 10.50,
        ];

        $query = ['name' => 'Bacon'];

        $fields = ['name', 'price'];

        $this->productsCollection
            ->shouldReceive('find')
            ->with(
                $query, ['name' => 1, 'price' => 1]
            )
            ->once()
            ->andReturn(
                $this->cursor
            );

        $this->cursor
            ->shouldReceive('count')
            ->once()
            ->andReturn(1);
        $this->cursor
            ->shouldReceive('rewind')
            ->once()
            ->andReturn($this->cursor);
        $this->cursor
            ->shouldReceive('current')
            ->once()
            ->andReturn($existentProduct);

        $result = _stubProduct::find($query, $fields);
        $this->assertEquals($existentProduct, $result->toArray());
    }

    public function testShouldWhereAsCachable()
    {
        $query = ['name' => 'Bacon'];

        $fields = ['name', 'price'];

        $this->productsCollection
            ->shouldReceive('find')
            ->with(
                $query, []
            )
            ->once()
            ->andReturn(
                $this->cursor
            );

        $this->cursor
            ->shouldReceive('rewind')
            ->once()
            ->andReturn($this->cursor);

        $result = _stubProduct::where($query, $fields, true);
        $this->assertInstanceOf('Zizaco\Mongolid\CachableOdmCursor', $result);
    }

    public function testShouldWhere()
    {
        $query = ['name' => 'Bacon'];

        $fields = ['name', 'price'];

        $this->productsCollection
            ->shouldReceive('find')
            ->with(
                $query, ['name' => 1, 'price' => 1]
            )
            ->once()
            ->andReturn(
                $this->cursor
            );

        $result = _stubProduct::where($query, $fields);
        $this->assertInstanceOf('Zizaco\Mongolid\OdmCursor', $result);
    }

    public function testShouldParseDocument()
    {
        $document = [
            '_id'   => new MongoId,
            'name'  => 'Bacon',
            'price' => 10.50,
        ];

        $prod = new _stubProduct;
        $prod->parseDocument($document);

        $this->assertEquals($document, $prod->attributes);
    }

    public function testGetAndSetAttribute()
    {
        $prod       = new _stubProduct;
        $prod->name = 'Bacon';
        $prod->setAttribute('price', 10.50);

        $this->assertEquals('Bacon', $prod->getAttribute('name'));
        $this->assertEquals(10.50, $prod->price);
    }

    public function testGetAtributes()
    {
        $prod        = new _stubProduct;
        $prod->name  = 'Bacon';
        $prod->price = 10.50;

        $this->assertEquals(
            ['name' => 'Bacon', 'price' => 10.50],
            $prod->getAttributes()
        );

        $this->assertEquals(
            ['name' => 'Bacon', 'price' => 10.50],
            $prod->attributes
        );
    }

    public function testGetMongoId()
    {
        $prod      = new _stubProduct;
        $prod->_id = 'theId';

        $this->assertEquals(
            'theId',
            $prod->getMongoId()
        );
    }

    public function testGetCollectionName()
    {
        $prod = new _stubProduct;

        $this->assertEquals(
            'test_products',
            $prod->getCollectionName()
        );
    }

    public function testShouldFill()
    {
        $document = [
            '_id'   => new MongoId,
            'name'  => 'Bacon',
            'price' => 10.50,
        ];

        // Empty fillable
        $prod = new _stubProduct;
        $prod->fill($document);
        $this->assertEquals($document, $prod->attributes);

        // Defined fillable
        $prod           = new _stubProduct;
        $prod->fillable = ['name'];
        $prod->fill($document);
        $this->assertEquals(['name' => 'Bacon'], $prod->attributes);

        // Defined guarded
        $prod          = new _stubProduct;
        $prod->guarded = ['name'];
        $prod->fill($document);
        $this->assertEquals(['_id' => $document['_id'], 'price' => 10.50], $prod->attributes);
    }

    public function testShouldCleanAttribute()
    {
        $prod        = new _stubProduct;
        $prod->name  = "Bacon";
        $prod->price = 10.50;

        $prod->cleanAttribute('name');
        unset($prod->price);

        $this->assertEquals([], $prod->attributes);
    }

    public function testShouldConvertToJson()
    {
        $prod        = new _stubProduct;
        $prod->name  = "Bacon";
        $prod->price = 10.50;

        $this->assertEquals(json_encode($prod->attributes), $prod->toJson());
    }

    public function testShouldConvertToArray()
    {
        $prod        = new _stubProduct;
        $prod->name  = "Bacon";
        $prod->price = 10.50;

        $this->assertEquals(['name' => 'Bacon', 'price' => 10.50], $prod->toArray());
    }

    public function testShouldReferenceOne()
    {
        $prod              = new _stubProduct;
        $prod->name        = "Bacon";
        $prod->price       = 10.50;
        $prod->category_id = new MongoId('51e1eefc065f908c10000411');

        $cat = [
            '_id'  => new MongoId('51e1eefc065f908c10000411'),
            'name' => 'BaconCategory',
        ];

        $query = ['_id' => $prod->category_id];

        $this->categoriesCollection
            ->shouldReceive('findOne')
            ->with(
                $query, []
            )
            ->twice()
            ->andReturn(
                $cat
            );

        // Cachable = false
        $result = $prod->category();
        $this->assertInstanceOf('_stubCategory', $result);

        // Cachable = true
        $result = $prod->category(true);
        $this->assertInstanceOf('_stubCategory', $result);
    }

    public function testShouldReferenceOneEvenWhenItsAnArray()
    {
        $prod              = new _stubProduct;
        $prod->name        = "Bacon";
        $prod->price       = 10.50;
        $prod->category_id = [new MongoId('51e1eefc065f908c10000411')]; // Within an array

        $cat = [
            '_id'  => new MongoId('51e1eefc065f908c10000411'),
            'name' => 'BaconCategory',
        ];

        $query = ['_id' => $prod->category_id[0]]; // Should query the first value of the array

        $this->categoriesCollection
            ->shouldReceive('findOne')
            ->with(
                $query, []
            )
            ->twice()
            ->andReturn(
                $cat
            );

        // Cachable = false
        $result = $prod->category();
        $this->assertInstanceOf('_stubCategory', $result);

        // Cachable = true
        $result = $prod->category(true);
        $this->assertInstanceOf('_stubCategory', $result);
    }

    public function testShouldReferenceMany()
    {
        $cat           = new _stubCategory;
        $cat->_id      = new MongoId('51e1eefc065f908c10000411');
        $cat->name     = 'BaconCategory';
        $cat->products = [new MongoId('51e1eefc065f908c10000411'), new MongoId('51e1eefc065f908c10000412')];

        $query = ['_id' => ['$in' => $cat->products]];

        $this->productsCollection
            ->shouldReceive('find')
            ->with(
                $query, []
            )
            ->twice()
            ->andReturn(
                $this->cursor
            );

        $result = $cat->products();
        $this->assertInstanceOf('Zizaco\Mongolid\OdmCursor', $result);

        $this->cursor
            ->shouldReceive('rewind')
            ->once()
            ->andReturn($this->cursor);

        $result = $cat->products(true);
        $this->assertInstanceOf('Zizaco\Mongolid\CachableOdmCursor', $result);
    }

    public function testShouldEmbedOne()
    {
        $attr1 = ['name' => 'color'];

        $cat                 = new _stubCategory;
        $cat->_id            = new MongoId('51e1eefc065f908c10000411');
        $cat->name           = 'BaconCategory';
        $cat->characteristic = $attr1;

        $result = $cat->characteristic();
        $this->assertInstanceOf('_stubCharacteristic', $result);
        $this->assertEquals('color', $result->name);

        $attr1 = ['name' => 'color'];

        $cat                 = new _stubCategory;
        $cat->_id            = new MongoId('51e1eefc065f908c10000411');
        $cat->name           = 'BaconCategory';
        $cat->characteristic = [$attr1];

        $result = $cat->characteristic();
        $this->assertInstanceOf('_stubCharacteristic', $result);
        $this->assertEquals('color', $result->name);
    }

    public function testShouldEmbedMany()
    {
        $attr1 = ['name' => 'color'];
        $attr2 = ['name' => 'material'];

        $cat                  = new _stubCategory;
        $cat->_id             = new MongoId('51e1eefc065f908c10000411');
        $cat->name            = 'BaconCategory';
        $cat->characteristics = [
            $attr1,
            $attr2,
        ];

        $result = $cat->characteristics();
        $this->assertEquals(2, count($result));
        $this->assertInstanceOf('_stubCharacteristic', $result[0]);
        $this->assertEquals('color', $result[0]->name);

        $this->assertInstanceOf('_stubCharacteristic', $result[1]);
        $this->assertEquals('material', $result[1]->name);
    }

    public function testShouldAttach()
    {
        $cat       = new _stubCategory;
        $cat->_id  = new MongoId('51e1eefc065f908c10000413');
        $cat->name = 'BaconCategory';

        $prod1      = new _stubProduct;
        $prod1->_id = new MongoId('51e1eefc065f908c10000411');
        $prod2      = ['_id' => new MongoId('51e1eefc065f908c10000412')];
        $prod3      = new MongoId('51e1eefc065f908c10000413');

        // Attach various "types" of products
        $cat->attach('products', $prod1); // Mongolid model object
        $cat->attach('products', $prod2); // Array
        $cat->attach('products', $prod3); // _id

        $this->assertContains($prod1->_id, $cat->products);
        $this->assertContains($prod2['_id'], $cat->products);
        $this->assertContains($prod3, $cat->products);

        // Now lets try with the alternate alias ;)
        unset($cat->products);
        $this->assertNull($cat->products);

        $cat->attachToProducts($prod1); // Mongolid model object
        $cat->attachToProducts($prod2); // Array
        $cat->attachToProducts($prod3); // _id

        $this->assertContains($prod1->_id, $cat->products);
        $this->assertContains($prod2['_id'], $cat->products);
        $this->assertContains($prod3, $cat->products);
    }

    public function testShouldDetach()
    {
        $cat           = new _stubCategory;
        $cat->_id      = new MongoId('51e1eefc065f908c10000413');
        $cat->name     = 'BaconCategory';
        $cat->products = [
            new MongoId('51e1eefc065f908c10000411'),
            new MongoId('51e1eefc065f908c10000412'),
            new MongoId('51e1eefc065f908c10000413'),
        ];

        $prod1      = new _stubProduct;
        $prod1->_id = $cat->products[0];
        $prod2      = ['_id' => $cat->products[1]];

        $cat->detach('products', $prod1);
        $this->assertNotContains($prod1->_id, $cat->products);
        $this->assertContains($prod2['_id'], $cat->products);
        $cat->detach('products', $prod2);
        $this->assertNotContains($prod2['_id'], $cat->products);
    }

    public function testShouldEmbed()
    {
        $cat       = new _stubCategory;
        $cat->name = 'BaconCategory';

        $char1       = new _stubCharacteristic;
        $char1->name = 'color';
        $char2       = ['_id' => new MongoId('51e1eefc065f908c10000412'), 'name' => 'material'];

        // Embed various "attributes" for products
        $cat->embed('characteristics', $char1); // Mongolid model object
        $cat->embed('characteristics', $char2); // Array

        $this->assertContains($char1->toArray(), $cat->characteristics);
        $this->assertContains($char2, $cat->characteristics);

        // Check if an _id was generated for the object
        $this->assertInstanceOf('MongoId', $char1->_id);

        // Now lets try with the alternate alias ;)
        unset($cat->characteristics);
        $this->assertNull($cat->characteristics);

        $cat->embedToCharacteristics($char1); // Mongolid model object
        $cat->embedToCharacteristics($char2); // Array

        $this->assertContains($char1->toArray(), $cat->characteristics);
        $this->assertContains($char2, $cat->characteristics);
    }

    public function testUpdateEmbeded()
    {
        $cat       = new _stubCategory;
        $cat->name = 'BaconCategory';

        $char1       = new _stubCharacteristic;
        $char1->name = 'color';

        // Embed color attribute
        $cat->embed('characteristics', $char1);

        $this->assertContains($char1->toArray(), $cat->characteristics);
        $this->assertEquals(1, count($cat->characteristics));

        // Check if an _id was generated for the object
        $this->assertInstanceOf('MongoId', $char1->_id);

        // Change object and update embeded document (since the _id is the same)
        $char1->name = 'puffins';
        $cat->embed('characteristics', $char1);

        $this->assertContains($char1->toArray(), $cat->characteristics);
        $this->assertEquals(1, count($cat->characteristics));

        // Make sure that the keys still begin from zero. This happens because
        // PHP array must have a correct sequence of keys in order to be considered
        // an array by the Mongo driver.
        $this->assertTrue(array_key_exists(0, $cat->characteristics));

    }

    public function testShouldUnembed()
    {
        $char1       = new _stubCharacteristic;
        $char1->_id  = new MongoId('51e1eefc065f908c10000411');
        $char1->name = 'color';
        $char2       = ['_id' => new MongoId('51e1eefc065f908c10000412'), 'name' => 'material'];
        $char3       = new _stubCharacteristic;
        $char3->_id  = new MongoId('51e1eefc065f908c10000412');
        $char3->name = 'nopah';

        $cat                  = new _stubCategory;
        $cat->_id             = new MongoId('51e1eefc065f908c10000413');
        $cat->name            = 'BaconCategory';
        $cat->characteristics = [
            $char1->toArray(),
            $char2,
            $char3->toArray(),
        ];

        $cat->unembed('characteristics', $char1);
        $this->assertNotContains($char1->toArray(), $cat->characteristics);
        $this->assertContains($char2, $cat->characteristics);
        $this->assertContains($char3->toArray(), $cat->characteristics);

        $cat->unembed('characteristics', $char2);
        $this->assertNotContains($char2, $cat->characteristics);
        $this->assertContains($char3->toArray(), $cat->characteristics);

        $cat->unembed('characteristics', $char3->_id);
        $this->assertNotContains($char3, $cat->characteristics);
    }

    public function testShouldPolymorph()
    {
        $prod1       = new _stubProduct;
        $prod1->_id  = new MongoId('51e1eefc065f908c10000411');
        $prod1->name = 'Bacon';

        $result = $prod1->polymorph($prod1);
        $this->assertEquals($prod1, $result);
    }

    /**
     * Prepare attributes to be used in MongoDb.
     * especially the _id.
     *
     * @param array $attr
     *
     * @return array
     */
    private function prepareMongoAttributes($attr)
    {
        // Translate the primary key field into _id
        if (isset($attr['_id'])) {
            // If its a 24 digits hexadecimal, then it's a MongoId
            if ($this->isMongoId($attr['_id'])) {
                $attr['_id'] = new \MongoId($attr['_id']);
            } elseif (is_numeric($attr['_id'])) {
                $attr['_id'] = (int) $attr['_id'];
            } else {
                $attr['_id'] = $attr['_id'];
            }
        }

        return $attr;
    }
}

class _stubProduct extends Model
{
    protected $database   = 'mongolid';
    protected $collection = 'test_products';

    public function category($cached = false)
    {
        return $this->referencesOne('_stubCategory', 'category_id', $cached);
    }
}

class _stubProductPersisted extends Model
{
    protected $database   = 'mongolid';
    protected $collection = 'test_products';
    protected $original   = ['name' => 'whatever', '_id' => '12312'];
    protected $attributes = ['desc' => 'whatever2', '_id' => '12312'];

    public function category($cached = false)
    {
        return $this->referencesOne('_stubCategory', 'category_id', $cached);
    }

    public function prepareTimestamps()
    {
    }
}

class _stubCategory extends Model
{
    protected $database   = 'mongolid';
    protected $collection = 'test_categories';

    public function products($cached = false)
    {
        return $this->referencesMany('_stubProduct', 'products', $cached);
    }

    public function characteristics()
    {
        return $this->embedsMany('_stubCharacteristic', 'characteristics');
    }

    public function characteristic()
    {
        return $this->embedsOne('_stubCharacteristic', 'characteristic');
    }
}

class _stubCharacteristic extends Model
{
    protected $collection = null;
}

class _stubCursor
{

    public $validCount = 1;

    public function valid()
    {
        $this->validCount--;

        return $this->validCount > 0;
    }

    public function limit()
    {
    }
}
