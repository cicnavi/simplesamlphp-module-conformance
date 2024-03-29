<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use Cicnavi\SimpleFileCache\SimpleFileCache;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Auth\Process\Conformance;
use SimpleSAML\Module\conformance\Errors\CacheException;
use Throwable;

/**
 * @psalm-suppress InvalidCatch
 */
class Cache
{
    protected CacheInterface $cache;

    /**
     * @throws CacheException
     */
    public function __construct(
        protected Configuration $sspConfig,
        CacheInterface $cache = null,
    ) {
        try {
            $this->cache = $cache ?? new SimpleFileCache(
                ModuleConfiguration::MODULE_NAME . '-cache',
                $this->sspConfig->getPathValue(ModuleConfiguration::KEY_DATADIR, sys_get_temp_dir())
            );
        } catch (Throwable $exception) {
            throw new CacheException('Error initializing cache instance: ' . $exception->getMessage());
        }
    }

    /**
     * @throws CacheException
     */
    public function getTestId(string $spEntityId): ?string
    {
        $cacheKeyTestId = $this->getTestIdCacheKey($spEntityId);
        try {
            /** @psalm-suppress MixedAssignment */
            $testId = $this->cache->get($cacheKeyTestId);
            $testId = empty($testId) ? null : (string)$testId;
        } catch (Throwable | InvalidArgumentException $exception) {
            throw new CacheException(
                'Error getting ' . $cacheKeyTestId . ' from cache: ' . $exception->getMessage()
            );
        }

        if (is_null($testId)) {
            return null;
        }

        try {
            $this->cache->delete($cacheKeyTestId);
        } catch (Throwable | InvalidArgumentException $exception) {
            throw new CacheException(
                'Error deleting ' . $cacheKeyTestId . ' from cache: ' . $exception->getMessage()
            );
        }

        return $testId;
    }

    /**
     * @throws CacheException
     */
    public function setTestId(string $testId, string $spEntityId): void
    {
        $cacheKeyTestId = $this->getTestIdCacheKey($spEntityId);
        try {
            $this->cache->set($cacheKeyTestId, $testId, 60);
        } catch (Throwable | InvalidArgumentException $exception) {
            throw new CacheException(
                sprintf(
                    'Error setting test ID (%s). Error was: %s',
                    var_export(compact('testId', 'spEntityId', 'cacheKeyTestId'), true),
                    $exception->getMessage()
                )
            );
        }
    }

    protected function getTestIdCacheKey(string $spEntityId): string
    {
        return hash('sha256', ModuleConfiguration::MODULE_NAME . '-' . Conformance::KEY_TEST_ID . '-' . $spEntityId);
    }
}
