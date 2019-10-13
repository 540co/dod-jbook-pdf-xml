<?php

namespace Keboola\Utils;

class BuildUrlTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildUrl()
    {
        $url = "endpoint?key=value&another=weird==thing";
        $this->assertEquals("endpoint?key=value&another=weird%3D%3Dthing&third=val", buildUrl($url, ['third' => 'val']));
    }
}
