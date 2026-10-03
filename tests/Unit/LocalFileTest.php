<?php

namespace JuanchoSL\VirtualDisk\Tests\Unit;

use JuanchoSL\VirtualDisk\Repositories\Local\NativeLocalEngine;

class LocalFileTest extends AbstractT
{

    public static function providerData(): array
    {
        foreach (['File', 'Local'] as $mode) {
            ${"tmp_folder_{$mode}"} = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'VirtualDisk', $mode]);
            if (!file_exists(${"tmp_folder_{$mode}"})) {
                mkdir(${"tmp_folder_{$mode}"}, 0777, true);
            }
        }
        return [
            'Local' => [new NativeLocalEngine($tmp_folder_Local)],
            'File' => [new NativeLocalEngine('file://' . $tmp_folder_File)]
        ];
    }
}