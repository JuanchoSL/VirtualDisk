<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Contracts;


interface FileInterface
{

    /**
     * Removes a regular file from disk
     * @param string $path Path, full or relative of file to delete
     * @return bool Result
     */
    public function remove(string $path): bool;

    /**
     * Write data into a file, if it does not exists, will be created
     * @param string $path Path, full or relative of file to write
     * @return bool Result
     */
    public function write(string $data, string $path): bool;

    /**
     * Read and returns the contents of a file
     * @param string $path Path, full or relative of file to delete
     * @return string|bool Return the contents or false if fail
     */
    public function read(string $path): string|false;

}