<?php

declare(strict_types=1);

namespace CpmsClient\Service;

use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;

/**
 * @method mixed get(string $endPointAlias, string $scope, array<string, mixed> $data = [])
 * @method mixed post(string $endPointAlias, string $scope, array<string, mixed> $data)
 * @method mixed put(string $endPointAlias, string $scope, array<string, mixed> $data)
 * @method mixed delete(string $endPointAlias, string $scope)
 */
class CacheAwareApiService
{
    protected StorageInterface $cacheStorage;

    public function __construct(private readonly ApiService $serviceProxy)
    {
    }

    public function getCacheStorage(): StorageInterface
    {
        return $this->cacheStorage;
    }

    public function setCacheStorage(StorageInterface $cacheStorage): void
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @param string $method
     * @param array<int, mixed> $arg
     *
     * @return mixed
     * @throws ExceptionInterface
     */
    public function __call($method, $arg)
    {
        $cacheKey = 'cache_' . md5((string) json_encode(array($method, $arg, $this->serviceProxy->getOptions()->getClientId())));

        if ($this->useCache($method) && $this->getCacheStorage()->hasItem($cacheKey)) {
            return $this->getCacheStorage()->getItem($cacheKey);
        } else {
            /** @var callable $callback */
            $callback = array($this->serviceProxy, $method);
            $result = call_user_func_array($callback, $arg);
            if ($this->useCache($method) && is_array($result) && !empty($result['items'])) {
                $this->getCacheStorage()->addItem($cacheKey, $result);
            }

            return $result;
        }
    }

    public function useCache(string $method): bool
    {
        return ($method == strtolower($method));
    }

    public function getServiceProxy(): ApiService
    {
        return $this->serviceProxy;
    }
}
