<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Repositories\Zip;

use DirectoryIterator;
use JuanchoSL\Validators\Types\Strings\StringValidations;
use JuanchoSL\VirtualDisk\Contracts\DiskInterface;
use JuanchoSL\VirtualDisk\Repositories\AbstractRepository;
use JuanchoSL\DataManipulation\Manipulators\Strings\StringsManipulators;
use Phar;
use PharData;

class ZipPharEngine extends AbstractRepository implements DiskInterface
{
    protected $con;
    protected $parent_path;
    public function __construct(string $parent_path)
    {
        if (str_starts_with($parent_path, 'phar://')) {
            $parent_path = substr($parent_path, 7);
        }
        if ((new StringValidations())->ifNot()->isValueStartingWith(static::SEPARATOR)->isValueContaining('\\')->__invoke($parent_path)) {
            $parent_path = (new StringsManipulators($parent_path))->replace("\\", "/")->replace("//", "/")->__tostring();
        }
        $this->path = 'phar://' . $parent_path;
        $this->open();

    }
    public function open()
    {
        $this->con = new PharData($this->path, \FilesystemIterator::UNIX_PATHS, null, Phar::ZIP);
        $this->con->startBuffering();
    }

    protected function internalPath(string $path): string
    {
        return ltrim(parent::absolutePath($path), static::SEPARATOR);
    }

    public function __destruct()
    {
        $this->con->stopBuffering();
        $this->con->compressFiles(Phar::GZ);
    }

    public function ls($path = '.'): iterable
    {
        $results = [];
        $resultss = new DirectoryIterator($this->virtualPath($path));
        foreach ($resultss as $result) {
            if ($result->isDir()) {
                $result = rtrim($result->getBasename(), self::SEPARATOR) . self::SEPARATOR;
            } elseif ($result->isFile()) {
                $result = rtrim($result->getBasename(), self::SEPARATOR);
            } else {
                continue;
            }
            $results[] = $result;
        }
        return array_values($results);
    }

    public function getStatus(): string
    {
        return '';
    }
    public function mkdir(string $internal_path): bool
    {
        $internal_path = $this->internalPath($internal_path);
        if ($this->exists($internal_path)) {
            return false;
        }
        $this->con->addEmptyDir($internal_path);
        return true;
    }

    public function rename(string $internal_path, string $new_path): bool
    {
        $internal_path = $this->internalPath($internal_path);
        $new_path = $this->internalPath($new_path);

        if ($this->exists($internal_path)) {

            $file = $this->con[$internal_path];
            if ($file->isDir()) {
                foreach ($this->ls($internal_path . static::SEPARATOR) as $result) {
                    $this->rename($internal_path . static::SEPARATOR . $result, $new_path . static::SEPARATOR . $result . static::SEPARATOR);
                }
                $this->con->copy($internal_path, $new_path);
                $internal_path = rtrim($internal_path, self::SEPARATOR) . self::SEPARATOR;
            } elseif ($file->isFile()) {
                $this->con->copy($internal_path, $new_path);
            }
            return $this->remove($internal_path);
        }
        return false;
    }

    public function rmdir(string $internal_path): bool
    {
        $internal_path = $this->internalPath($internal_path);
        if ($this->exists($internal_path)) {
            foreach ($this->ls($internal_path . static::SEPARATOR) as $file) {
                if ($this->isDir($internal_path . static::SEPARATOR . $file)) {
                    $this->rmdir($internal_path . static::SEPARATOR . $file);
                } elseif ($this->isFile($internal_path . static::SEPARATOR . $file)) {
                    return $this->remove($internal_path . static::SEPARATOR . $file);
                }
            }
            $internal_path = rtrim($internal_path, self::SEPARATOR);
            $this->con->delete($internal_path);
            return true;
        }
        return false;
    }

    public function remove(string $internal_path): bool
    {
        $internal_path = $this->internalPath($internal_path);
        if ($this->exists($internal_path)) {
            $this->con->delete(trim($internal_path, static::SEPARATOR));
            return true;
        }
        return false;
    }

    public function read(string $internal_path): string|false
    {
        $file = $this->con[$this->internalPath($internal_path)];
        $result = $file->openFile()->fread($file->getSize());
        return $result !== false ? (string) $result : false;
    }

    public function write(string $content, string $internal_path): bool
    {
        $internal_path = $this->internalPath($internal_path);
        if ($this->exists($internal_path)) {
            return $this->con[$internal_path]->openFile('w')->fwrite($content) !== false;
        }
        $this->con->addFromString($this->absolutePath($internal_path), $content);
        return true;
    }

    public function exists(string $path): bool
    {
        return $this->con->offsetExists($this->internalPath($path));
    }

    public function isDir(string $path): bool
    {
        return $this->con[$this->internalPath($path)]->isDir();
    }

    public function isFile(string $path): bool
    {
        return $this->con[$this->internalPath($path)]->isFile();
    }

    public function stat(string $path): iterable|false
    {
        $a = $this->con[$this->internalPath($path)];
        $size = $a->isDir() ? 'sizd' : 'size';
        $result = [
            'name' => $path,
            $size => $a->getSize(),
            'unique' => '',
            'UNIX.mode' => (string) (new StringsManipulators(decoct($a->getPerms())))->substring(-4),//substr(decoct($res['mode']), -4),
            'UNIX.uid' => $a->getOwner() ?? '',
            'UNIX.gid' => $a->getGroup() ?? '',
            'modify' => $a->getMTime()
        ];
        if ($path == '.') {
            $result['type'] = 'cdir';
        } elseif ($path == '..') {
            $result['type'] = 'pdir';
        } else {
            $result['type'] = $size == 'sizd' ? 'dir' : 'file';
        }
        return $result;
    }

    public function truncate(string $path): bool
    {
        $internal_path = $this->internalPath($path);

        if ($this->exists($internal_path)) {
            $file = $this->con[$internal_path];
            if ($file->isDir()) {
                $results = $this->ls($internal_path . static::SEPARATOR);
                if (!empty($results)) {
                    foreach ($results as $result) {
                        if ($this->isDir($internal_path . static::SEPARATOR . $result)) {
                            $this->rmdir($internal_path . static::SEPARATOR . $result);
                        } elseif ($file->isFile()) {
                            $this->remove($internal_path . static::SEPARATOR . $result);
                        }
                    }
                    return true;
                }
            }
        }
        return false;
    }
}