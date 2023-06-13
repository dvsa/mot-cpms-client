<?php
namespace CpmsClient\Service;

use Laminas\Cache\Storage\StorageInterface;
use Laminas\Log\LoggerAwareTrait;

/**
 * Class ApiService
 * @method get
 * @method post
 * @method put
 * @method delete
 *
 * @package CpmsClient\Service
 */
class CacheAwareApiService
{
    use LoggerAwareTrait;
    /**
     * @var ApiService
     */
    protected $serviceProxy;

    /** @var  StorageInterface */
    protected $cacheStorage;

    public function __construct(ApiService $service)
    {
        $this->serviceProxy = $service;
    }

    /**
     * @return StorageInterface
     */
    public function getCacheStorage()
    {
        return $this->cacheStorage;
    }

    /**
     * @param StorageInterface $cacheStorage
     */
    public function setCacheStorage($cacheStorage)
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @param $method
     * @param $arg
     *
     * @return mixed
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function __call($method, $arg)
    {
        $cacheKey = 'cache_' . md5(json_encode(array($method, $arg, $this->serviceProxy->getOptions()->getClientId())));

        if ($this->useCache($method) && $this->getCacheStorage()->hasItem($cacheKey)) {
            return $this->getCacheStorage()->getItem($cacheKey);
        } else {
            $result = call_user_func_array(array($this->serviceProxy, $method), $arg);
            if ($this->useCache($method) && !empty($result['items'])) {
                $this->getCacheStorage()->addItem($cacheKey, $result);
            }

            return $result;
        }
    }

    /**
     * @param $method
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
