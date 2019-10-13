<?php

namespace Keboola\Utils;

class FormatDateTimeTest extends \PHPUnit_Framework_TestCase
{

    public function testFormatDateTime()
    {
        $this->assertEquals(formatDateTime("15.2.2014 16:00", "Y-m-d H:i"), "2014-02-15 16:00");
        $this->assertEquals(formatDateTime("now"), date(DATE_W3C));
    }
}
