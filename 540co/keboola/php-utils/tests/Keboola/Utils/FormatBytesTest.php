<?php

namespace Keboola\Utils;

class FormatBytesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider baseFormatProvider
     * @param string $expectedFormat
     * @param int $bytes
     */
    public function testBaseFormat($expectedFormat, $bytes)
    {
        $this->assertEquals($expectedFormat, formatBytes($bytes));
    }

    /**
     * @return array
     */
    public function baseFormatProvider()
    {
        return [
            ['-1 B', -1],
            ['0 B', 0],
            ['1 B', 1],
            ['1 kB', 1024],
            ['1 MB', 1024 * 1024],
            ['1 GB', 1024 * 1024 * 1024],
            ['1 TB', 1024 * 1024 * 1024 * 1024],
            ['1 PB', 1024 * 1024 * 1024 * 1024 * 1024],
            ['1024 PB', 1024 * 1024 * 1024 * 1024 * 1024 * 1024],
            ['100.05 kB', 102451],
            ['24.82 kB', 25414.5415],
            ['5.29 kB', '5415.5154'],
            ['5.16 MB', '5411515,5455'],
            ['1 B', true],
            ['0 B', false],
            ['0 B', null],
            ['0 B', 'foo'],
            ['0 B', ''],
            ['0 B', []],
        ];
    }

    /**
     * @dataProvider precisionFormatProvider
     * @param string $expectedFormat
     * @param int $bytes
     * @param int $precision
     */
    public function testPrecision($expectedFormat, $bytes, $precision)
    {
        $this->assertEquals($expectedFormat, formatBytes($bytes, $precision));
    }

    /**
     * @return array
     */
    public function precisionFormatProvider()
    {
        $bytes = 102451541598;
        return [
            ['-95 GB', -$bytes, -50],
            ['-95 GB', -$bytes, 0],
            ['95 GB', $bytes, -1],
            ['95 GB', $bytes, 0],
            ['95.4 GB', $bytes, 1],
            ['95.42 GB', $bytes, 2],
            ['95.415 GB', $bytes, 3],
            ['95.4154 GB', $bytes, 4],
            ['95.41543 GB', $bytes, 5],
            ['95.415433 GB', $bytes, 6],
            ['95.4154335 GB', $bytes, 7],
            ['95.41543349 GB', $bytes, 8],
            ['95.415433494 GB', $bytes, 9],
            ['95.4154334944 GB', $bytes, 10],
            ['-95.4154334944 GB', -$bytes, 10],
            ['95.4 GB', $bytes, true],
            ['95 GB', $bytes, false],
            ['95 GB', $bytes, null],
            ['95 GB', $bytes, []],
            ['95 GB', $bytes, 'foo'],
            ['95.4 GB', $bytes, '1.1'],
            ['95.415 GB', $bytes, '2.5'],
            ['95.42 GB', $bytes, '2,7'],
        ];
    }
}
