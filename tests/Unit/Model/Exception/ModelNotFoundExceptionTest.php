<?php

namespace Mongolid\Model\Exception;

use Mongolid\TestCase;

final class ModelNotFoundExceptionTest extends TestCase
{
    public function testSetModel(): void
    {
        // Set
        $object = new ModelNotFoundException();
        $object->setModel('User');

        // Actions
        $modelResult = $object->getModel();
        $messageResult = $object->getMessage();

        // Assertions
        $this->assertSame('User', $modelResult);
        $this->assertSame(
            'No query results for model [User].',
            $messageResult
        );
    }
}
