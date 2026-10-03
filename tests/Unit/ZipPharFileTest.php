<?php

namespace JuanchoSL\VirtualDisk\Tests\Unit;

use JuanchoSL\VirtualDisk\Repositories\Zip\ZipPharEngine;

class ZipPharFileTest extends AbstractT
{

    public static function providerData(): array
    {
        $tmp_folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'VirtualDisk';
        if (!file_exists($tmp_folder)) {
            mkdir($tmp_folder, 0777, true);
        }
        return [
            "zipp" => [new ZipPharEngine($tmp_folder . DIRECTORY_SEPARATOR . date("YmdHi") . "-pharzipile.zip")]
        ];
    }
}