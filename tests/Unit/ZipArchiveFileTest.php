<?php

namespace JuanchoSL\VirtualDisk\Tests\Unit;

use JuanchoSL\VirtualDisk\Repositories\Zip\ZipArchiveEngine;
use PHPUnit\Framework\TestCase;

class ZipArchiveFileTest extends TestCase
{

    public static function providerData(): array
    {
        $tmp_folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'VirtualDisk';
        if (!file_exists($tmp_folder)) {
            mkdir($tmp_folder, 0777, true);
        }
        return [
            "zip" => [new ZipArchiveEngine($tmp_folder . DIRECTORY_SEPARATOR . date("YmdHi") . "-archivezipfile.zip")],
        ];
    }

    /**
     * @dataProvider providerData
     */
    public function testFull($engine)
    {
        $engine->chdir('/');
        $this->assertFalse($engine->exists('not-exists'), $engine->getStatus());

        $engine->chdir('/');
        $this->assertEmpty($engine->ls(), $engine->getStatus());

        $engine->chdir('/');
        $this->assertTrue($engine->mkdir('test/'), $engine->getStatus());

        $engine->chdir('/');
        $this->assertNotEmpty($engine->ls(), $engine->getStatus());
        //$this->markTestSkipped();

        $engine->chdir('/');
        //$this->assertFalse($engine->mkdir('test'), $engine->getStatus());

        $engine->chdir('/');
        $this->assertTrue($engine->rename('test/', 'renamed-test/'), $engine->getStatus());

        $engine->chdir('/');
        $this->assertTrue($engine->write("Lorem ipsun dolor", 'test.txt'), $engine->getStatus());

        $engine->chdir('/');
        $stat = $engine->stat('test.txt');
        $this->assertIsArray($stat, $engine->getStatus());
        $this->assertArrayHasKey('type', $stat, $engine->getStatus());
        $this->assertArrayHasKey('size', $stat, $engine->getStatus());
        $this->assertArrayHasKey('modify', $stat, $engine->getStatus());
        $this->assertArrayHasKey('name', $stat, $engine->getStatus());

        $engine->chdir('/');
        $this->assertTrue($engine->rename('test.txt', 'renamed-test.txt'), $engine->getStatus());

        $engine->chdir('/');
        $this->assertFalse($engine->rename('test.txt', 'renamed-test.txt'), $engine->getStatus());

        $engine->chdir('/');
        $this->assertTrue($engine->remove('renamed-test.txt'), $engine->getStatus());

        $engine->chdir('/');
        $this->assertFalse($engine->remove('renamed-test.txt'), $engine->getStatus());

        //$this->markTestSkipped();
        $engine->chdir('/');
        //$this->assertTrue($engine->truncate('renamed-test/'), $engine->getStatus());

        //$this->markTestSkipped();
        $engine->chdir('/');
        //$this->assertFalse($engine->truncate('renamed-test/'), $engine->getStatus());

        $engine->chdir('/');
        $this->assertTrue($engine->rmdir('renamed-test/'), $engine->getStatus());

        $engine->chdir('/');
        $this->assertFalse($engine->rmdir('renamed-test/'), $engine->getStatus());

        //$this->markTestSkipped();
        $engine->chdir('/');
        //$this->assertTrue($engine->truncate('.'), $engine->getStatus());
    }
}