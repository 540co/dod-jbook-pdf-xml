<?php

use Keboola\Utils\Utils,
    Keboola\Utils\Exception\JsonDecodeException;

class UtilsTest extends \PHPUnit_Framework_TestCase {

	public function testFormatDateTime()
	{
		$this->assertEquals(Utils::formatDateTime("15.2.2014 16:00", "Y-m-d H:i"), "2014-02-15 16:00");
		$this->assertEquals(Utils::formatDateTime("now"), date(DATE_W3C));
	}

	public function testReplaceDatesInArray()
	{
		$array = [
			'some ~~yesterday~~ thing',
			'arr' => [
				'key' => 'something deeper from ~~-6 days~~'
			],
			'another' => [
				'oh hi' => [
					'deep as adele! ~~20071231~~'
				]
			]
		];

		$parsed = [
			'some ' . Utils::formatDateTime('yesterday') . ' thing',
			'arr' => [
				'key' => 'something deeper from ' . Utils::formatDateTime('-6 days'),
			],
			'another' => [
				'oh hi' => [
				0 => 'deep as adele! 2007-12-31T00:00:00+00:00',
				],
			],
		];

		$this->assertEquals(Utils::replaceDatesInArray($array, '~~'), $parsed);
	}

	public function testReplaceDates()
	{
		$this->assertEquals(Utils::replaceDates("@@now@@", "@@"), date(DATE_W3C));
		$this->assertEquals(Utils::replaceDates("%%now%%"), date(DATE_W3C));
	}

	public function testGetDataFromPath() {
		$data = array(
			"p" => array(
				"a" => array(
					"t" => array(
						"h" => "Hello world!"
					)
				)
			)
		);
		$slash = Utils::getDataFromPath("p/a/t/h", $data);
		$this->assertEquals($slash, $data["p"]["a"]["t"]["h"]);
		$dot = Utils::getDataFromPath("p.a.t.h", $data, ".");
		$this->assertEquals($dot, $data["p"]["a"]["t"]["h"]);
		$null = Utils::getDataFromPath("p/a/t/g", $data);
		$this->assertEquals($null, "");
	}

	public function testIsValidDateTimeString()
	{
		$this->assertEquals(Utils::isValidDateTimeString("Fri, 31 Dec 1999 23:59:59 GMT", DATE_RFC1123), true);
		$this->assertEquals(Utils::isValidDateTimeString("Fri, 31 Dec 1999 23:59:59 +0000", DATE_RFC1123), true);
		$this->assertEquals(Utils::isValidDateTimeString("2005-08-15T15:52:01+00:00", DATE_W3C), true);
	}

	public function testBuildEvalString()
	{
		$this->assertEquals(Utils::buildEvalString('md5(attr[hello])', ['hello' => 'world']), 'return md5(\'world\');');
		$this->assertEquals(eval(Utils::buildEvalString('md5(attr[hello] . "Me!")', ['hello' => 'world'])), md5("worldMe!"));
	}

	/**
	 * @expectedException \Keboola\Utils\Exception\EvalStringException
	 * @expectedExceptionMessage Function 'die' is not allowed!
	 */
	public function testBuildEvalStringIllegalFunction()
	{
		Utils::buildEvalString('die("mf")');
	}

	public function testBuildEvalStringIllegalFunctionInAttr()
	{
		$something = "very secret server data!";
		$this->assertEquals(eval(Utils::buildEvalString('attr[test]', ['test' => 'var_dump($something)'])), 'var_dump($something)');
	}

	public function testFlattenArray()
	{
		$multi = [
			'a' => 'b',
			'c' => [
				'd' => 'e',
				'f' => [
					'g' => 'h'
				]
			]
		];

		$single = [
			'a' => 'b',
			'c.d' => 'e',
			'c.f.g' => 'h'
		];

		$this->assertEquals($single, Utils::flattenArray($multi));
	}

	public function testBuildUrl()
	{
		$url = "endpoint?key=value&another=weird==thing";
		$this->assertEquals("endpoint?key=value&another=weird%3D%3Dthing&third=val", Utils::buildUrl($url, ['third' => 'val']));
	}

	public function testArrayToObject()
	{
		$array = [
			'str' => 'string',
			'arr' => ['a','b','c'],
			'obj' => [
				'd' => 'dee',
				'e' => 'eh?'
			],
			'arrOfObj' => [
				['f' => 'g'],
				['h' => 'i']
			]
		];

		$object = (object) [
			'str' => 'string',
			'arr' => ['a','b','c'],
			'obj' => (object) [
				'd' => 'dee',
				'e' => 'eh?'
			],
			'arrOfObj' => [
				(object) ['f' => 'g'],
				(object) ['h' => 'i']
			]
		];

		$this->assertEquals($object, Utils::arrayToObject($array));
	}

	public function testObjectToArray()
	{
		$array = [
			'str' => 'string',
			'arr' => ['a','b','c'],
			'obj' => [
				'd' => 'dee',
				'e' => 'eh?'
			],
			'arrOfObj' => [
				['f' => 'g'],
				['h' => 'i']
			]
		];

		$object = (object) [
			'str' => 'string',
			'arr' => ['a','b','c'],
			'obj' => (object) [
				'd' => 'dee',
				'e' => 'eh?'
			],
			'arrOfObj' => [
				(object) ['f' => 'g'],
				(object) ['h' => 'i']
			]
		];

		$this->assertEquals($array, Utils::objectToArray($object));
	}

	public function testIsEmptyObject()
	{
		$this->assertTrue(Utils::isEmptyObject(new \stdClass));
		$this->assertTrue(Utils::isEmptyObject((object) ['item' => new \stdClass]));
		$this->assertFalse(Utils::isEmptyObject((object) ['item' => 'value']));
	}

	public function testJsonDecodeLint()
	{
        $expected = <<<EOT
Parse error on line 1:
{"a": {[]}
------^
Expected one of: 'STRING', '}'
EOT;

        try {
            $str = Utils::json_decode('{"a": {[]}', false, 512, 0, false, true);
        } catch(JsonDecodeException $e) {
            self::assertEquals($expected, $e->getData()['errDetail']);
            $err = $e;
        }

        self::assertInstanceOf('Keboola\Utils\Exception\JsonDecodeException', $err);
	}
}
