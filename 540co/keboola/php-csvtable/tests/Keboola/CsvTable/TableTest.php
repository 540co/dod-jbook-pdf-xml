<?php
/**
 * User: kachnitel
 * Date: 7/12/13
 * Time: 3:32 PM
 */

namespace Keboola\Temp\Tests;

use Keboola\CsvTable\Table;

class TempTest extends \PHPUnit_Framework_TestCase
{
	public function testCreate()
	{
		/** @var \SplFileInfo $file */
		$table = Table::create('filename_suffix', ['first_col', 'second_col']);
		$this->assertFileExists($table->getPathName());
		$this->assertEquals(
			'"first_col","second_col"
',
			file_get_contents($table->getPathName())
		);
		$this->assertEquals(
			array (
				0 => 'first_col',
				1 => 'second_col',
			),
			$table->getHeader()
		);
	}

	public function testPrimaryKey()
	{
        $table = Table::create('pk', ['id', 'user', 'data']);
        $table->setPrimaryKey('id,user');
        $this->assertEquals('id,user', $table->getPrimaryKey());
        $this->assertEquals(['id', 'user'], $table->getPrimaryKey(true));
	}

	public function testPrimaryKeyArray()
	{
        $table = Table::create('pk', ['id', 'user', 'data']);
        $table->setPrimaryKey(['id', 'user']);
        $this->assertEquals('id,user', $table->getPrimaryKey());
        $this->assertEquals(['id', 'user'], $table->getPrimaryKey(true));
	}

	public function testPrimaryKeyEmpty()
	{
        $table = Table::create('pk', ['id', 'user', 'data']);
        $this->assertNull($table->getPrimaryKey());
    }
}
