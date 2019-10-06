<?php

namespace Keboola\Utils;

class SanitizeUtf8Test extends \PHPUnit_Framework_TestCase
{

    public function testSanitize()
    {
        $sanitized = sanitizeUtf8("SQLSTATE[XX000]: " . chr(0x00000080) . " abcd");
        $this->assertEquals("SQLSTATE[XX000]: ? abcd", $sanitized);
    }
}
