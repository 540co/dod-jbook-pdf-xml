<?php

namespace Keboola\Utils;

class SanitizeColumnNameTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider namesToSanitize
     **/
    public function testSanitizeColumnName($nameToSanitize, $sanitizedName)
    {
        $sanitized = sanitizeColumnName($nameToSanitize);
        $this->assertEquals($sanitizedName, $sanitized);
    }

    public function namesToSanitize()
    {
        return [
            [
                "_~dlažební  %_kostky_~",
                "dlazebni__kostky"
            ],[
                "test-vn-đá cuội",
                "test_vn_da_cuoi"
            ],[
                "jp日本語",
                "jp"
            ]
        ];
    }
}
