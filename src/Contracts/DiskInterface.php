<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Contracts;


interface DiskInterface
{

    /**
     * Create a new directory, path can be absolute or relative to the current selected dir
     * @param string $path Path for the new directory
     * @return bool The action result
     */
    public function mkdir(string $path): bool;

    /**
     * Remove a directory, path can be absolute or relative to the current selected dir
     * @param string $path
     * @return bool The action result
     */
    public function rmdir(string $path): bool;

    /**
     * Show the current selected directory
     * @return string The current directory
     */
    public function cwdir(): string;

    /**
     * Change the current selected directory
     * @param string $path
     * @return bool The action result
     */
    public function chdir(string $path): bool;

    /**
     * Summary of ls
     * @param string $path
     * @return void
     */
    public function ls(string $path = '.'): iterable;

    /**
     * Summary of truncate
     * @param string $path
     * @return void
     */
    public function truncate(string $path): bool;
}