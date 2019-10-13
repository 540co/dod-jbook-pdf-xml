<?php

namespace Keboola\Utils;

class ReplaceDatesTest extends \PHPUnit_Framework_TestCase
{

    public function testReplaceDates()
    {
        $this->assertEquals(replaceDates("@@now@@", "@@"), date(DATE_W3C));
        $this->assertEquals(replaceDates("%%now%%"), date(DATE_W3C));
    }
}
