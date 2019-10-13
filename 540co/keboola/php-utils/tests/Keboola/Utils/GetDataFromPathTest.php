<?php

namespace Keboola\Utils;

class GetDataFromPathTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDataFromPath()
    {
        $data = array(
            "p" => array(
                "a" => array(
                    "t" => array(
                        "h" => "Hello world!"
                    )
                )
            )
        );
        $slash = getDataFromPath("p/a/t/h", $data);
        $this->assertEquals($slash, $data["p"]["a"]["t"]["h"]);
        $dot = getDataFromPath("p.a.t.h", $data, ".");
        $this->assertEquals($dot, $data["p"]["a"]["t"]["h"]);
        $null = getDataFromPath("p/a/t/g", $data);
        $this->assertEquals($null, "");
    }

    /**
     * @expectedException \Keboola\Utils\Exception\NoDataFoundException
     */
    public function testGetDataFromPathException()
    {
        $data = array(
            "p" => array(
                "a" => array(
                    "t" => array(
                        "h" => "Hello world!"
                    )
                )
            )
        );
        getDataFromPath("a/b/c", $data, "/", false);
    }

    public function testGetDataFromPath0()
    {
        $data = array(
            array(
                array(
                    "t" => array(
                        "h" => "Hello world!"
                    )
                )
            )
        );
        $slash = getDataFromPath("0/0/t/h", $data);
        $this->assertEquals($slash, $data[0][0]["t"]["h"]);
        $dot = getDataFromPath("0.0.t.h", $data, ".");
        $this->assertEquals($dot, $data[0][0]["t"]["h"]);
        $null = getDataFromPath("0/0/t/g", $data);
        $this->assertEquals($null, "");
    }
}
