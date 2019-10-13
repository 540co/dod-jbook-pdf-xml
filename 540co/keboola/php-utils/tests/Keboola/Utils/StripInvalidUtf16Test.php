<?php
/**
 * Author: miro@keboola.com
 * Date: 20/12/2017
 */

namespace Keboola\Utils;

class StripInvalidUtf16Test extends \PHPUnit_Framework_TestCase
{
    public function testStripInvalidUtf16()
    {
        $striped = stripInvalidUtf16("\\uD83D\\uDE0A\\uD83D\\uDC9CSomething's\\uD83D\\uDC9C\\uD83D\\uDE0A wrong");
        $this->assertEquals("Something's wrong", $striped);
    }
}
