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

    public function testFlattenArrayGlue()
    {
        $multi = [
            'a' => 'b',
            'c' => [
                'd' => 'e',
                'f' => [
                    'g' => 'h',
                    'i' => [
                        'j' => 'k'
                    ]
                ]
            ]
        ];

        $single = [
            'a' => 'b',
            'c_d' => 'e',
            'c_f_g' => 'h',
            'c_f_i_j' => 'k',
        ];

        $this->assertEquals($single, flattenArray($multi, '', '_'));
    }
}
