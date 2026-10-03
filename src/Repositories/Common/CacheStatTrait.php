<?php declare(strict_types=1);

namespace JuanchoSL\VirtualDisk\Repositories\Common;

use JuanchoSL\SimpleCache\Repositories\ProcessCache;
use Psr\SimpleCache\CacheInterface;

trait CacheStatTrait
{

    protected CacheInterface $cache;


    public function getCacheRepository(): CacheInterface
    {
        return $this->cache ?? new ProcessCache(md5(get_class($this)));
    }

    public function setCacheRepository(CacheInterface $cache): static
    {
        $this->cache = $cache;
        return $this;
    }

}