<?php
/**
 * User: kachnitel
 * Date: 7/12/13
 * Time: 3:32 PM
 */

namespace Keboola\Temp\Tests;

use Keboola\Temp\Temp;

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

    public function testCreateFileNested()
    {
        $temp = new Temp();
        $file = $temp->createFile('dir/file');

        self::assertFileExists($temp->getTmpFolder() . '/dir/file');

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

    public function testCleanup()
    {
        $temp = new Temp();
        $file = $temp->createFile('file');
        $deeperFile = $temp->createFile('dir/file2');

        $dir = $temp->getTmpFolder();

        self::assertFileExists($dir . '/file');
        self::assertFileExists($dir . '/dir/file2');

        unset($temp);
        self::assertFileNotExists($dir);
    }

    public function testCleanupForeignFile()
    {
        $temp = new Temp();
        $temp->initRunFolder();

        $dir = $temp->getTmpFolder();

        touch($dir . '/file');
        self::assertFileExists($dir . '/file');

        unset($temp);
        self::assertFileNotExists($dir);
    }

    public function testCleanupFilePreserve()
    {
        $temp = new Temp();
        $file = $temp->createFile('file', true);

        unset($temp);
        self::assertFileExists($file->getPathname());
        unlink($file->getPathname());
        rmdir(dirname($file->getPathname()));
    }
}
