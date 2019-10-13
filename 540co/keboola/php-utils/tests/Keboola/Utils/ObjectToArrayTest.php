<?php

namespace Keboola\Utils;

class ObjectToArrayTest extends \PHPUnit_Framework_TestCase
{
    public function testObjectToArray()
    {
        $array = [
            'str' => 'string',
            'arr' => ['a','b','c'],
            'obj' => [
                'd' => 'dee',
                'e' => 'eh?'
            ],
            'arrOfObj' => [
                ['f' => 'g'],
                ['h' => 'i']
            ]
        ];

        $object = (object) [
            'str' => 'string',
            'arr' => ['a','b','c'],
            'obj' => (object) [
                'd' => 'dee',
                'e' => 'eh?'
            ],
            'arrOfObj' => [
                (object) ['f' => 'g'],
                (object) ['h' => 'i']
            ]
        ];

        $this->assertEquals($array, objectToArray($object));
    }
}
