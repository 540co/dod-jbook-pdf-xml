<?php

namespace Keboola\Utils;

class FlattenArrayTest extends \PHPUnit_Framework_TestCase
{
    public function testFlattenArray()
    {
        $multi = [
            'a' => 'b',
            'c' => [
                'd' => 'e',
                'f' => [
                    'g' => 'h'
                ]
            ]
        ];

        $single = [
            'a' => 'b',
            'c.d' => 'e',
            'c.f.g' => 'h'
        ];

        $this->assertEquals($single, flattenArray($multi));
    }
}
