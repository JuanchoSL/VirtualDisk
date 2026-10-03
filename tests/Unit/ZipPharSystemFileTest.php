<?php

namespace JuanchoSL\VirtualDisk\Tests\Unit;

use JuanchoSL\VirtualDisk\Repositories\Zip\ZipPharFileSystemEngine;

class ZipPharSystemFileTest extends AbstractT
{

    public static function providerData(): array
    {
        $tmp_folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'VirtualDisk';
        if (!file_exists($tmp_folder)) {
            mkdir($tmp_folder, 0777, true);
        }
        $file = $tmp_folder . DIRECTORY_SEPARATOR . date("YmdHi") . "-pharzipfilesystem.phar";
        if (file_exists($file)) {
            unlink($file);
        }
        return [
            "zippf" => [new ZipPharFileSystemEngine("phar://" . $file)]
        ];
    }
}