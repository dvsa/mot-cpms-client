<?php

declare(strict_types=1);

namespace CpmsClient\Service;

use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;

/**
 * Class ApiService
 * @method mixed get(string $endPointAlias, string $scope, array<string, mixed> $data = [])
 * @method mixed post(string $endPointAlias, string $scope, array<string, mixed> $data)
 * @method mixed put(string $endPointAlias, string $scope, array<string, mixed> $data)
 * @method mixed delete(string $endPointAlias, string $scope)
 *
 * @package CpmsClient\Service
 */
class CacheAwareApiService
{
    protected StorageInterface $cacheStorage;

    public function __construct(private readonly ApiService $serviceProxy)
    {
    }

    /**
     * @return StorageInterface
     */
    public function getCacheStorage(): StorageInterface
    {
        return $this->cacheStorage;
    }

    /**
     * @param StorageInterface $cacheStorage
     */
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

    /**
     * @param string $method
     *
     * @return bool
     */
    public function useCache($method): bool
    {
        return ($method == strtolower($method));
    }

    /**
     * @return ApiService
     */
    public function getServiceProxy(): ApiService
    {
        return $this->serviceProxy;
    }
}
