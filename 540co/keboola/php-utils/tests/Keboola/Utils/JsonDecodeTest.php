<?php

namespace Keboola\Utils;

class JsonDecodeTest extends \PHPUnit_Framework_TestCase
{
    public function testJsonDecodeLint()
    {
        $expected = <<<EOT
Parse error on line 1:
{"a": {[]}
------^
Expected one of: 'STRING', '}'
EOT;

        try {
            $str = jsonDecode('{"a": {[]}', false, 512, 0, false, true);
        } catch (Exception\JsonDecodeException $e) {
            self::assertEquals($expected, $e->getData()['errDetail']);
            $err = $e;
        }

        self::assertInstanceOf('Keboola\Utils\Exception\JsonDecodeException', $err);
    }
}
