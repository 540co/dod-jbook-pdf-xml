<?php

namespace Keboola\Utils;

class IsEmptyObjectTest extends \PHPUnit_Framework_TestCase
{

    public function testIsEmptyObject()
    {
        $this->assertTrue(isEmptyObject(new \stdClass));
        $this->assertTrue(isEmptyObject((object) ['item' => new \stdClass]));
        $this->assertFalse(isEmptyObject((object) ['item' => 'value']));
    }
}
