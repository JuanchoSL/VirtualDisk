<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Repositories;

use JuanchoSL\DataManipulation\Manipulators\Strings\StringsManipulators;
use JuanchoSL\Validators\Types\Strings\StringValidations;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AbstractRepository implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    const SEPARATOR = '/';

    protected $current = self::SEPARATOR;

    protected string $path = '';


    protected function virtualPath(string $path): string
    {
        if (!str_starts_with($path, $this->path)) {
            $path = (string) (new StringsManipulators($this->path))
                ->rtrim(static::SEPARATOR)
                ->concatenation($this->absolutePath($path), '')
            ;
        }
        return $path;
    }

    protected function absolutePath(string $path): string
    {
        $current = [];
        if (substr($path, 0, 1) != static::SEPARATOR) {
            $current = array_filter(explode(static::SEPARATOR, (string) (new StringsManipulators($this->cwdir()))->trim(static::SEPARATOR)));
        }
        $path_stack = array_filter(explode(static::SEPARATOR, (string) (new StringsManipulators($path))->trim(static::SEPARATOR)));
        foreach ($path_stack as $segment) {
            if ($segment === '..') {
                if (empty($current)) {
                    throw new \RuntimeException(sprintf("Invalid path: %s", $path));
                }
                array_pop($current);
            } elseif ($segment !== '.' && $segment !== '') {
                $current[] = $segment;
            }
        }
        return (string) (new StringsManipulators(implode(static::SEPARATOR, $current)))->preppend(static::SEPARATOR, '');
        /*
        $finalpath = (new StringsManipulators(implode(static::SEPARATOR, $current)))->preppend(static::SEPARATOR, '');
        if (substr($path, -1) == static::SEPARATOR) {
            //$finalpath = $finalpath->rtrim(static::SEPARATOR)->concatenation(static::SEPARATOR, '');
        }
        return (string) $finalpath;
        */
    }

    protected function checkPath(string $path)
    {
        return ltrim($this->virtualPath($path), static::SEPARATOR);
        /*
        if (substr($path, -1) !== static::SEPARATOR) {
            $path .= static::SEPARATOR;
        }
        if (substr($path, 0, 1) != static::SEPARATOR) {
            if ($path === '.') {
                $path = $this->current;
            } else {
                $path = trim($this->current, static::SEPARATOR) . static::SEPARATOR . ltrim(trim($path, '.'), static::SEPARATOR);
            }
        }
        return ltrim($path, static::SEPARATOR);
        */
    }

    public function cwdir(): string
    {
        return $this->current;
    }

    public function chdir(string $path): bool
    {
        $current = $this->absolutePath($path);
        if (!$this->exists($current)) {
            return false;
        }
        $this->current = $current;
        return true;
    }

    public function truncate(string $path): bool
    {
        $path = $this->absolutePath($path);
        if ($this->exists($path)) {
            if ($this->isDir($path)) {
                $current = $this->cwdir();
                if ($this->chdir($path)) {
                    $files = $this->ls();
                    if (!empty($files) && is_iterable($files)) {
                        foreach ($files as $file) {
                            if (in_array($file, ['.', '..'])) {
                                continue;
                            }
                            if ($this->isDir($file)) {
                                $this->rmdir($file);
                            } else {
                                $this->remove($file);
                            }
                        }
                    }
                    $this->chdir($current);
                }
            }
        } else {
            return false;
        }
        return $this->ls($path) === [];
    }

    public function tree()
    {
        $results = [$this->current => []];
        foreach ($this->ls() as $i => $result) {
            if ((new StringValidations())->isValueContaining(static::SEPARATOR)->isValueEndingWith(static::SEPARATOR)->__invoke($result)) {
                //if (substr_count($result, static::SEPARATOR) > 0 && substr($result, -1) == static::SEPARATOR) {
                $current = $this->cwdir();
                $this->chdir($result);
                //$this->current = $result;
                $results[$current][] = $this->tree();
                //$this->current = $current;
                $this->chdir($current);
            } else {
                $results[$this->current][$i] = $result;
            }
        }
        return $results;
    }

    public function list(string $dir = '.', $results = [])
    {
        $validator = new StringValidations();
        foreach ($this->ls() as $i => $result) {
            if ($validator->clear()->isValueContaining(static::SEPARATOR)->isValueEndingWith(static::SEPARATOR)->__invoke($result)) {
                //if (substr_count($result, static::SEPARATOR) > 0 && substr($result, -1) == static::SEPARATOR) {
                $current = $this->cwdir();
                $this->chdir($result);
                //$this->current = $result;
                $results = array_merge($results, $this->list('.'));
                //$this->current = $current;
                $this->chdir($current);
            } elseif ($validator->clear()->isValueEqualsAny('bmp', 'jpg', 'jpeg', 'png')->__invoke(strtolower(pathinfo($result, PATHINFO_EXTENSION)))) {
                //} elseif (in_array(strtolower(pathinfo($result, PATHINFO_EXTENSION)), ['bmp', 'jpg', 'jpeg', 'png'])) {
                $val = rtrim($this->absolutePath($this->cwdir()), '/') . '/' . $result;
                //echo $val.PHP_EOL;
                $results[rtrim($this->absolutePath($this->cwdir()), '/') . '/' . $result] = hash_file('sha256', rtrim($this->virtualPath($this->cwdir()), '/') . '/' . $result);
            }
        }
        return $results;
    }

    abstract public function exists(string $path): bool;
    abstract public function ls(string $path = '.'): iterable;
    abstract public function isDir(string $path): bool;
    abstract public function rmdir(string $path): bool;
    abstract public function write(string $contents, string $path): bool;
    abstract public function read(string $path): string|false;
    abstract public function remove(string $path): bool;
    abstract public function rename(string $path_original, string $path_destiny): bool;
}