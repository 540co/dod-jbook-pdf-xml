<?php

namespace Keboola\Utils;

class ToAsciiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider testStrings
     **/
    public function testToAscii($testString, $expectedAscii)
    {
        $asciid = toAscii($testString);
        $this->assertEquals($expectedAscii, $asciid);
    }

    public function testStrings()
    {
        return [
            [
                "_~dlažební  %_kostky_~",
                "_~dlazebni  %_kostky_~"
            ],[
                "test-vn-đá cuội",
                "test-vn-da cuoi"
            ],[
                "jp日本語",
                "jp???"
            ]
        ];
    }
}
