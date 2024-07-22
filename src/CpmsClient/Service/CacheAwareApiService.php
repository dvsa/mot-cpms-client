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

    /**
     * @param ApiService $serviceProxy
     * @param LoggerInterface $logger
     * @param StorageInterface $cacheStorage
     */
    public function __construct(
        protected ApiService $serviceProxy,
        protected $logger,
        protected StorageInterface $cacheStorage
    ) {
    }

    /**
     * @return StorageInterface
     * @throws Exception
     */
    public function getCacheStorage()
    {
        return $this->cacheStorage;
    }

    /**
     * @param StorageInterface $cacheStorage
     * @return void
     */
    public function setCacheStorage($cacheStorage)
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @param string $method
     * @param array $arg
     *
     * @return mixed
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function __call($method, $arg)
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

    /**
     * @param string $method
     *
     * @return bool
     */
    public function useCache($method)
    {
        return ($method == strtolower($method));
    }

    /**
     * @return ApiService
     */
    public function getServiceProxy()
    {
        return $this->serviceProxy;
    }
}
