<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use Cicnavi\SimpleFileCache\Exceptions\CacheException;
use Cicnavi\SimpleFileCache\SimpleFileCache;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;

class Cache
{
    protected CacheInterface $cache;

    public function __construct(CacheInterface $cache = null)
    {
        $this->cache = $cache ?? new SimpleFileCache(ModuleConfig::MODULE_NAME . '-cache');
    }

    /**
     * // TODO mivanci Implement custom exception handling
     * @throws InvalidArgumentException
     * @throws \Cicnavi\SimpleFileCache\Exceptions\InvalidArgumentException
     * @throws CacheException
     */
    public function getTestId(string $spEntityId): ?string
    {
        $cacheKeyTestId = $this->getTestIdCacheKey($spEntityId);
        $testId = $this->cache->get($cacheKeyTestId);

        if (is_null($testId)) {
            return null;
        }

        $this->cache->delete($cacheKeyTestId);

        return (string)$testId;
    }

    public function setTestId(string $testId, string $spEntityId): void
    {
        $cacheKeyTestId = $this->getTestIdCacheKey($spEntityId);
        $this->cache->set($cacheKeyTestId, $testId, 60);
    }

    protected function getTestIdCacheKey(string $spEntityId): string
    {
        return hash('sha256', ModuleConfig::MODULE_NAME . '-' . Conformance::KEY_TEST_ID . '-' . $spEntityId);
    }
}
