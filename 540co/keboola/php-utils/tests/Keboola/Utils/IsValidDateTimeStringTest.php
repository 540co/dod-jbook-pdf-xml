<?php

namespace Keboola\Utils;

class IsValidDateTimeStringTest extends \PHPUnit_Framework_TestCase
{

    public function testIsValidDateTimeString()
    {
        $this->assertEquals(isValidDateTimeString("Fri, 31 Dec 1999 23:59:59 GMT", DATE_RFC1123), true);
        $this->assertEquals(isValidDateTimeString("Fri, 31 Dec 1999 23:59:59 +0000", DATE_RFC1123), true);
        $this->assertEquals(isValidDateTimeString("2005-08-15T15:52:01+00:00", DATE_W3C), true);
    }


    public function testIsInvalidDateTimeString()
    {
        $this->assertEquals(isValidDateTimeString("abcd", DATE_RFC1123), false);
    }
}
