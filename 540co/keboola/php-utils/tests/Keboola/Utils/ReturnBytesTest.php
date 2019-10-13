<?php

namespace Keboola\Utils;

class ReturnBytesTest extends \PHPUnit_Framework_TestCase
{

    public function testKilo()
    {
        $this->assertEquals(1024, returnBytes("1K"));
    }

    public function testMega()
    {
        $this->assertEquals(1024*1024, returnBytes("1M"));
    }

    public function testGiga()
    {
        $this->assertEquals(1024*1024*1024, returnBytes("1G"));
    }
}
