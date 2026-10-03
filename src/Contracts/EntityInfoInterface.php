<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Contracts;


interface EntityInfoInterface
{
    /**
     * Summary of exists
     * @param string $path
     * @return void
     */
    public function exists(string $path): bool;

    /**
     * Summary of isDir
     * @param string $path
     * @return void
     */
    public function isDir(string $path): bool;

    /**
     * Summary of isFile
     * @param string $path
     * @return void
     */
    public function isFile(string $path): bool;

    /**
     * Summary of stat
     * @param string $path
     * @return void
     */
    public function stat(string $path): iterable|false;
}