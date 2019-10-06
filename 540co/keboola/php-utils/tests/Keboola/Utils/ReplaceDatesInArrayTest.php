<?php

namespace Keboola\Utils;

class ReplaceDatesInArrayTest extends \PHPUnit_Framework_TestCase
{

    public function testReplaceDatesInArray()
    {
        $array = [
            'some ~~yesterday~~ thing',
            'arr' => [
                'key' => 'something deeper from ~~-6 days~~'
            ],
            'another' => [
                'oh hi' => [
                    'deep as adele! ~~20071231~~'
                ]
            ]
        ];

        $parsed = [
            'some ' . formatDateTime('yesterday') . ' thing',
            'arr' => [
                'key' => 'something deeper from ' . formatDateTime('-6 days'),
            ],
            'another' => [
                'oh hi' => [
                0 => 'deep as adele! 2007-12-31T00:00:00+00:00',
                ],
            ],
        ];

        $this->assertEquals(replaceDatesInArray($array, '~~'), $parsed);
    }
}
