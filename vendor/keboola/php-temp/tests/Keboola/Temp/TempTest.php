<?php
/**
 * User: kachnitel
 * Date: 7/12/13
 * Time: 3:32 PM
 */

namespace Keboola\Temp\Tests;

use \Keboola\Temp\Temp;

class TempTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateTmpFile()
    {
        $temp = new Temp('test');

        $tempFolder = $temp->getTmpFolder();

        /** @var \SplFileInfo $file */
        $file = $temp->createTmpFile('filename_suffix');

        $this->assertFileExists($file->getPathname());
        $this->assertContains($tempFolder, $file->getPathname());
    }

    public function testCreateFile()
    {
        $temp = new Temp();

        $file = $temp->createFile('test');

        self::assertInstanceOf('SplFileInfo', $file);
        self::assertEquals($temp->getTmpFolder() . '/' . $file->getFilename(), $file->getPathname());
    }

    public function testGetTmpFolder()
    {
        $temp = new Temp('test');

        $tempFolder = $temp->getTmpFolder();

        $this->assertNotEmpty($tempFolder);
        $this->assertContains(sys_get_temp_dir() . '/test', $temp->getTmpFolder());
    }

    public function testSetTmpFolder()
    {
        $temp = new Temp('test');
        $temp->setId("aabb");
        $expectedTmpDir = sys_get_temp_dir() . "/test/aabb";
        $this->assertEquals($expectedTmpDir, $temp->getTmpFolder());

        $file = $temp->createFile('file');
        self::assertFileExists(sys_get_temp_dir() . "/test/aabb/file");
    }
}
