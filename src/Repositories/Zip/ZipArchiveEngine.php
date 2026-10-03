<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Repositories\Zip;

use JuanchoSL\DataManipulation\Manipulators\Strings\StringsManipulators;
use JuanchoSL\Validators\Types\Strings\StringValidations;
use JuanchoSL\VirtualDisk\Contracts\DiskInterface;
use JuanchoSL\VirtualDisk\Repositories\AbstractRepository;
use ZipArchive;

class ZipArchiveEngine extends AbstractRepository implements DiskInterface
{
    protected $con;

    public function __construct(string $path)
    {
        if (str_starts_with($path, 'zip://')) {
            $path = substr($path, 6);
        }
        if ((new StringValidations())->ifNot()->isValueStartingWith(static::SEPARATOR)->isValueContaining('\\')->__invoke($path)) {
            $path = (new StringsManipulators($path))->replace("\\", "/")->replace("//", "/")->__tostring();
        }
        $this->path = $path;
        $this->open();
    }
    public function open()
    {
        $resource = new ZipArchive();
        //$resource->open($this->path, ZipArchive::CREATE | ZipArchive::AFL_CREATE_OR_KEEP_FILE_FOR_EMPTY_ARCHIVE);
        //$resource->open($this->path, ZipArchive::CREATE | ZipArchive::FL_OPEN_FILE_NOW);
        //$resource->open($this->path, 0);
        $resource->open($this->path, (file_exists($this->path)) ? 0 : ZipArchive::CREATE);
        $this->con = $resource;
    }

    public function tree()
    {
        $results = [$this->current => []];
        foreach ($this->ls() as $i => $result) {
            if ($this->isDir($result)) {
                $current = $this->current;
                $this->current = $result;
                $results[$current][] = $this->tree();
                $this->current = $current;
            } else {
                $results[$this->current][$i] = $result;
            }
        }
        return $results;
    }

    public function ls($subdirectorio = '.'): iterable
    {
        if (substr($subdirectorio, -1) !== static::SEPARATOR) {
            $subdirectorio .= static::SEPARATOR;//falla si activamos con solo punto
        }
        $results = [];
        $subdirectorio = ($subdirectorio === './') ? $this->current : $subdirectorio;
        $subdirectorio = trim($subdirectorio, static::SEPARATOR);
        $subdirectorio .= (!empty($subdirectorio) && substr($subdirectorio, -1) !== static::SEPARATOR) ? static::SEPARATOR : '';
        for ($i = 0; $i < $this->con->numFiles; $i++) {
            $archivoInfo = $this->con->statIndex($i);
            if (!is_array($archivoInfo) or !array_key_exists('name', $archivoInfo) or empty($archivoInfo['name'])) {
                continue;
            }
            if (empty($subdirectorio) or (strpos($archivoInfo['name'], $subdirectorio) === 0 && $archivoInfo['name'] !== $subdirectorio)) {
                $nombreRelativo = str_replace($subdirectorio, '', $archivoInfo['name']);
                if (str_contains($nombreRelativo, static::SEPARATOR)) {
                    $subdirs = count(array_filter(explode(static::SEPARATOR, $nombreRelativo)));
                    if ($subdirs > 1) {
                        continue;
                    }
                }
                if (!empty($nombreRelativo)) {
                    $results[] = $nombreRelativo;
                }
            }
        }
        return $results;
    }
    public function chdir(string $path): bool
    {
        if (substr($path, -1) !== static::SEPARATOR) {
            $path .= static::SEPARATOR;
        }
        if (!$this->exists($path)) {
            return false;
        }
        $this->current = $this->absolutePath($path);
        return true;
    }

    public function write(string $data, string $path): bool
    {
        return $this->con->addFromString($this->internalPath($path), $data);
    }

    public function read(string $path): string
    {
        return $this->con->getFromName($this->internalPath($path));
    }

    public function remove(string $path): bool
    {
        return ($this->exists($path)) ? $this->con->deleteName($this->internalPath($path)) : false;
    }

    public function mkdir(string $name): bool
    {
        if (substr($name, -1) !== static::SEPARATOR) {
            $name .= static::SEPARATOR;
        }
        if ($this->exists($name)) {
            return false;
        }
        //$name = $this->absolutePath($name);
        if (defined('ZipArchive::FL_OPEN_FILE_NOW')) {
            $result = $this->con->addEmptyDir($this->internalPath($name), ZipArchive::FL_OPEN_FILE_NOW | ZipArchive::FL_LOCAL | ZipArchive::FL_CENTRAL);// or throw new ConflictException(sprintf("The directory %s already exists", $name));
        } else {
            $result = $this->con->addEmptyDir(ltrim($name, static::SEPARATOR), ZipArchive::FL_LOCAL | ZipArchive::FL_CENTRAL);// or throw new ConflictException(sprintf("The directory %s already exists", $name));
        }

        //$result = $this->con->addEmptyDir(ltrim($name, static::SEPARATOR));// or throw new ConflictException(sprintf("The directory %s already exists", $name));
        $this->close();
        $this->open();
        return $result;
        return ($result || $this->con->status == ZipArchive::ER_OK);
    }

    public function rename(string $old_name, string $new_name): bool
    {
        if (!$this->exists($old_name) OR $this->exists($new_name)) {
            return false;
        }
        $old_path = $this->internalPath($old_name);
        $new_path = $this->internalPath($new_name);
        for ($i = 0; $i < $this->con->numFiles; $i++) {
            $item = $this->con->statIndex($i)['name'];
            if (str_starts_with($item, $old_path)) {
                $new_item = str_replace($old_path, $new_path, $item);
                $this->con->renameIndex($i, $new_item);
            }
        }
        $result = in_array($this->con->status, [ZipArchive::ER_OK, ZipArchive::ER_CHANGED]);
        $this->close();
        $this->open();
        return (!$this->exists($old_name) AND $this->exists($new_name));
        return $result;

    }

    public function rmdir(string $path): bool
    {
        if (substr($path, -1) !== static::SEPARATOR) {
            $path .= static::SEPARATOR;
        }
        if (!$this->exists($path)) {
            return false;
        }
        $path = $this->internalPath($path);
        for ($i = 0; $i < $this->con->numFiles; $i++) {
            $item = $this->con->statIndex($i);
            if ($item !== false && str_starts_with($item['name'], $path)) {
                $this->con->deleteIndex($i);
            }
        }
        return $this->con->status === ZipArchive::ER_DELETED;
    }

    public function stat(string $path): iterable|false
    {
        $index = $this->con->locateName($this->internalPath($path));
        $stat = $this->con->statIndex($index);
        return [
            "name" => $stat['name'],
            "size" => $stat['size'],
            "modify" => $stat['mtime'],
            "type" => (substr($stat['name'], -1) == '/' ? 'dir' : 'file')
        ];
        $stat['type'] = (substr($stat['name'], -1) == '/' ? 'dir' : 'file');
        return $stat;
    }

    public function isFile(string $path): bool
    {
        return ($this->exists($path) && !$this->isDir($path));
    }

    public function isDir(string $path): bool
    {
        if (!$this->exists($path)) {
            return false;
        }
        return (substr($this->stat($path)['name'], -1) === static::SEPARATOR);
    }

    public function exists(string $path): bool
    {
        if ($path == '.') {
            $path = $this->cwdir();
        }
        return (empty($path)) ? true : ($this->con->locateName($this->internalPath($path)) !== false);
    }

    public function close()
    {
        if (!empty($this->con) && $this->con instanceof ZipArchive && $this->con->status !== ZipArchive::ER_ZIPCLOSED && !empty($this->con->filename)) {
            @$this->con->close();
            unset($this->con);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function getStatus()
    {
        return $this->con->getStatusString();
    }

    public function truncate(string $path): bool
    {
        if ($this->exists($path)) {
            foreach ($this->ls($path) as $item) {
                $this->rmdir($path . static::SEPARATOR . $item);
            }
            return true;
        }
        return false;
    }

    protected function absolutePath(string $path): string
    {
        $new_path = parent::absolutePath($path);
        if (substr($path, -1) == static::SEPARATOR && substr($new_path, -1) != static::SEPARATOR) {
            $new_path .= static::SEPARATOR;
        }
        return $new_path;
    }
    protected function internalPath(string $path): string
    {
        return ltrim($this->absolutePath($path), static::SEPARATOR);
    }
}