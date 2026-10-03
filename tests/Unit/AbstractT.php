<?php

namespace JuanchoSL\VirtualDisk\Tests\Unit;

use JuanchoSL\CurlClient\Wrappers\PsrCurlClient;
use JuanchoSL\HttpData\Factories\RequestFactory;
use JuanchoSL\HttpData\Factories\StreamFactory;
use JuanchoSL\VirtualDisk\Repositories\Dropbox\DropboxRepository;
use JuanchoSL\VirtualDisk\Repositories\Ftp\CurlFtpEngine;
use JuanchoSL\VirtualDisk\Repositories\Ftp\FtpEngine;
use JuanchoSL\VirtualDisk\Repositories\Ftp\StreamContextFtpEngine;
use JuanchoSL\VirtualDisk\Repositories\Local\NativeLocalEngine;
use JuanchoSL\VirtualDisk\Repositories\Samba\SambaCurlEngine;
use JuanchoSL\VirtualDisk\Repositories\Samba\SambaFileSystemEngine;
use JuanchoSL\VirtualDisk\Repositories\WebDAV\WebDavEngine;
use JuanchoSL\VirtualDisk\Repositories\Zip\StreamContextZipEngine;
use JuanchoSL\VirtualDisk\Repositories\Zip\ZipArchiveEngine;
use JuanchoSL\VirtualDisk\Repositories\Zip\ZipPharEngine;
use JuanchoSL\HttpData\Factories\UriFactory;
use PHPUnit\Framework\TestCase;

abstract class AbstractT extends TestCase
{

    abstract public static function providerData(): array;

    /**
     * @dataProvider providerData
     */
    public function testOpen($engine)
    {
        $this->assertTrue(true);
    }
    /**
     * @dataProvider providerData
     */
    public function testNotExists($engine)
    {
        $engine->chdir('/');
        $this->assertFalse($engine->exists('not-exists'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testNotList($engine)
    {
        $engine->chdir('/');
        $this->assertEmpty($engine->ls(), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testMakeDir($engine)
    {
        $engine->chdir('/');
        $this->assertTrue($engine->mkdir('test/'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testList($engine)
    {
        $engine->chdir('/');
        $this->assertNotEmpty($engine->ls(), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testFailMakeDir($engine)
    {
        $engine->chdir('/');
        $this->assertFalse($engine->mkdir('test/'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testRenameDir($engine)
    {
        $engine->chdir('/');
        $this->assertTrue($engine->rename('test/', 'renamed-test/'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testWriteFile($engine)
    {
        //$engine->chdir('/');
        $this->assertTrue($engine->write("Lorem ipsun dolor", 'test.txt'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testReadFile($engine)
    {
        $engine->chdir('/');
        $this->assertEquals("Lorem ipsun dolor", $engine->read('test.txt'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testStatFile($engine)
    {
        $engine->chdir('/');
        $stat = $engine->stat('test.txt');
        $this->assertIsArray($stat, $engine->getStatus());
        $this->assertArrayHasKey('type', $stat, $engine->getStatus());
        $this->assertArrayHasKey('size', $stat, $engine->getStatus());
        $this->assertArrayHasKey('modify', $stat, $engine->getStatus());
        $this->assertArrayHasKey('name', $stat, $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testRenameFile($engine)
    {
        $engine->chdir('/');
        $this->assertTrue($engine->rename('test.txt', 'renamed-test.txt'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testFailRenameFile($engine)
    {
        $engine->chdir('/');
        $this->assertFalse($engine->rename('test.txt', 'renamed-test.txt'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testDeleteFile($engine)
    {
        $engine->chdir('/');
        $this->assertTrue($engine->remove('renamed-test.txt'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testFailDeleteFile($engine)
    {
        $engine->chdir('/');
        $this->assertFalse($engine->remove('renamed-test.txt'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testTruncateDir($engine)
    {
        $this->markTestSkipped();
        $engine->chdir('/');
        $this->assertTrue($engine->truncate('renamed-test/'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testFailTruncateDir($engine)
    {
        $this->markTestSkipped();
        $engine->chdir('/');
        $this->assertFalse($engine->truncate('renamed-test/'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testDeleteDir($engine)
    {
        $engine->chdir('/');
        $this->assertTrue($engine->rmdir('renamed-test/'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testFailDeleteDir($engine)
    {
        $engine->chdir('/');
        $this->assertFalse($engine->rmdir('renamed-test/'), $engine->getStatus());
    }
    /**
     * @dataProvider providerData
     */
    public function testClear($engine)
    {
        $this->markTestSkipped();
        $engine->chdir('/');
        $this->assertTrue($engine->truncate('.'), $engine->getStatus());
    }
}