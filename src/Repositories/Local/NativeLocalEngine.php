<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Repositories\Local;

use JuanchoSL\DataManipulation\Manipulators\Strings\StringsManipulators;
use JuanchoSL\Validators\Types\Entities\EntityValidations;
use JuanchoSL\Validators\Types\Strings\StringValidations;
use JuanchoSL\VirtualDisk\Contracts\DiskInterface;
use JuanchoSL\VirtualDisk\Contracts\EntityInfoInterface;
use JuanchoSL\VirtualDisk\Contracts\FileInterface;
use JuanchoSL\VirtualDisk\Repositories\AbstractRepository;
use JuanchoSL\VirtualDisk\Repositories\ErrorsTrait;

class NativeLocalEngine extends AbstractRepository implements DiskInterface, FileInterface, EntityInfoInterface
{
    //use ErrorsTrait;

    protected $context = null;

    public function __construct(string $parent_path)
    {
        if (str_starts_with($parent_path, 'file://')) {
            $parent_path = substr($parent_path, 7);
        }
        if ((new StringValidations())->ifNot()->isValueStartingWith(static::SEPARATOR)->isValueContaining('\\')->__invoke($parent_path)) {
            $parent_path = (new StringsManipulators($parent_path))->replace("\\", "/")->replace("//", "/")->__tostring();
        }
        $this->path = $parent_path;
        return;
    }

    public function open()
    {
        if (!$this->exists($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function ls(string $path = '.'): iterable
    {
        $virtual_path = rtrim($this->virtualPath($path), static::SEPARATOR);
        $results = @scandir($virtual_path, SCANDIR_SORT_ASCENDING, $this->context);
        if (!is_iterable($results)) {
            $results = [];
        }
        foreach ($results as $index => $result) {
            if (in_array($result, ['.', '..'])) {
                unset($results[$index]);
                continue;
            }
            if ($this->isDir($virtual_path . static::SEPARATOR . $result)) {
                $result = rtrim($result, static::SEPARATOR) . static::SEPARATOR;
            }
            $results[$index] = $result;
        }
        return array_values($results);
    }

    public function read(string $file_path): string|false
    {
        return @file_get_contents(rtrim($this->virtualPath($file_path), static::SEPARATOR), false, $this->context);
    }

    public function write(string $data, string $file_path): bool
    {
        return @file_put_contents(rtrim($this->virtualPath($file_path), static::SEPARATOR), $data, FILE_APPEND, $this->context) !== false;
    }

    public function remove(string $file_path): bool
    {
        return @unlink(rtrim($this->virtualPath($file_path), static::SEPARATOR), $this->context);
    }

    public function mkdir(string $name): bool
    {
        $real_path = $this->virtualPath($name);
        return @mkdir($real_path, 0777, true, $this->context);
    }

    public function rename(string $old_name, string $new_name): bool
    {
        $old_name = $this->virtualPath($old_name);
        $new_name = $this->virtualPath($new_name);
        return (@rename($old_name, $new_name, $this->context));
    }

    public function rmdir(string $path): bool
    {
        $real_path = $this->virtualPath($path);
        return (@rmdir($real_path, $this->context));
    }

    public function exists(string $path): bool
    {
        return @file_exists($this->virtualPath($path));
    }

    public function stat(string $path): iterable|false
    {
        $path = $this->virtualPath($path);
        return [
            'name' => basename($path),
            'modify' => filemtime($path),
            'size' => filesize($path),
            'type' => is_dir($path) ? 'dir' : 'file',
        ];
        return stat($this->virtualPath($path));
    }

    public function isDir(string $path): bool
    {
        $stat = $this->stat($path);
        $val = (new EntityValidations())->isKeyContaining('type')->isValueAttributeValidating('type', (new StringValidations())->isValueEquals('dir'));
        return $val($stat);
        return is_dir($this->virtualPath($path));
    }

    public function isFile(string $path): bool
    {
        return !$this->isDir($path);
    }

    public function close()
    {
        //closedir($this->con);
    }

    public function getStatus(): string
    {
        return error_get_last()['message'] ?? '';
    }

    public function __destruct()
    {
        $this->close();
    }
}