<?php

namespace Keboola\Utils;

class CamelizeTest extends \PHPUnit_Framework_TestCase
{
    public function testCamelize()
    {
        $this->assertEquals("oneTwoThreeFour", camelize("one_two-three four"));
    }

    public function testCamelizeLowerFirst()
    {
        $this->assertEquals("OneTwoThreeFour", camelize("one_two-three four", true));
    }
}
