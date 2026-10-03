<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Repositories\Zip;

use JuanchoSL\DataManipulation\Manipulators\Strings\StringsManipulators;
use JuanchoSL\Validators\Types\Strings\StringValidations;
use JuanchoSL\VirtualDisk\Contracts\DiskInterface;
use JuanchoSL\VirtualDisk\Contracts\EntityInfoInterface;
use JuanchoSL\VirtualDisk\Contracts\FileInterface;
use JuanchoSL\VirtualDisk\Repositories\Local\NativeLocalEngine;
use Phar;

class ZipPharFileSystemEngine extends NativeLocalEngine implements DiskInterface, FileInterface, EntityInfoInterface
{

    public function __construct(string $parent_path)
    {
        $this->context = stream_context_create(
            ['phar' => ['compress' => Phar::ZIP]],
            //['metadata' => ['user' => 'cellog']]
        );

        if (str_starts_with($parent_path, 'phar://')) {
            $parent_path = substr($parent_path, 7);
        }
        if ((new StringValidations())->ifNot()->isValueStartingWith(static::SEPARATOR)->isValueContaining('\\')->__invoke($parent_path)) {
            $parent_path = (new StringsManipulators($parent_path))->replace("\\", "/")->replace("//", "/")->__tostring();
        }
        $this->path = "phar://" . $parent_path;
    }

    public function isDir(string $path): bool
    {
        return !$this->isFile($path);
    }

    public function isFile(string $path): bool
    {
        $stat = $this->stat($path);
        return (!empty($stat) && array_key_exists('size', $stat) && $stat['size'] > 0);
    }

    public function write(string $data, string $file_path): bool
    {
        $fo = fopen($this->virtualPath($file_path), 'w+', false, $this->context);
        $writed = fwrite($fo, $data);
        fclose($fo);
        return $writed !== false;
    }
}