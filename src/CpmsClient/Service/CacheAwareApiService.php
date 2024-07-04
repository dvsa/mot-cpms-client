<?php

namespace CpmsClient\Service;

use Exception;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Log\LoggerAwareTrait;
use Laminas\Log\LoggerInterface;

/**
 * Class ApiService
 *
 * @package CpmsClient\Service
 */
class CacheAwareApiService
{
    use LoggerAwareTrait;

    protected ApiService $serviceProxy;

    protected StorageInterface $cacheStorage;

    public function __construct(ApiService $service, LoggerInterface $logger, StorageInterface $cacheStorage)
    {
        $this->serviceProxy = $service;
        $this->logger = $logger;
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @throws Exception
     */
    public function getCacheStorage(): StorageInterface
    {
        return $this->cacheStorage;
    }

    public function setCacheStorage(StorageInterface $cacheStorage): void
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function __call(string $method, array $arg): mixed
    {
        /** @var string $json */
        $json = json_encode(array($method, $arg, $this->serviceProxy->getOptions()->getClientId()));
        $cacheKey = 'cache_' . md5($json);

        if ($this->useCache($method) && $this->getCacheStorage()->hasItem($cacheKey)) {
            return $this->getCacheStorage()->getItem($cacheKey);
        } else {
            /** @var callable $func */
            $func = array($this->serviceProxy, $method);

            $result = call_user_func_array($func, $arg);
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
