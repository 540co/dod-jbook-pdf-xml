<?php

namespace Keboola\Utils;

class HttpBuildUrlTest extends \PHPUnit_Framework_TestCase
{
    public function testHttpBuildUrl()
    {
        $parts = [
            "scheme" => "https",
            "user" => "user",
            "pass" => "pass",
            "host" => "test.com",
            "port" => 8080,
            "path" => "path",
            "query" => "var1=val1&var2=val2",
            "fragment" => "fragment"
        ];
        $this->assertEquals("https://user:pass@test.com:8080path?var1=val1&var2=val2#fragment", httpBuildUrl($parts));
    }
}
