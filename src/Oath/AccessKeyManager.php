<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Oath;

use Magento\Framework\App\CacheInterface;
use Webbhuset\CollectorCheckout\Config\StoreConfigFactory;
use Webbhuset\CollectorCheckoutSDK\Adapter\CurlWithAccessKey;
use Webbhuset\CollectorCheckoutSDK\Adapter\GetAccessKey;

class AccessKeyManager
{
    const CACHE_TTL = 3600; // 1 hour
    const CACHE_NAME = 'WALLEY_OATH_ACCESS_KEY';
    const CACHE_TAGS = 'WALLEY';
    /**
     * @var CacheInterface
     */
    private $cache;
    /**
     * @var StoreConfigFactory
     */
    private StoreConfigFactory $storeConfigFactory;

    public function __construct(
        CacheInterface $cache,
        StoreConfigFactory $storeConfig
    ) {
        $this->cache = $cache;
        $this->storeConfigFactory = $storeConfig;
    }

    public function getAccessKeyByStore(int $storeId)
    {
        $cacheKey = $this->getCacheKey($storeId);
        $accessKey = $this->cache->load($cacheKey);
        if ($accessKey) {
            return $accessKey;
        }
        $accessKey = $this->generateNewAccessKey($storeId);
        $this->cache->save($accessKey,$cacheKey,[self::CACHE_TAGS],self::CACHE_TTL);

        return $accessKey;
    }

    private function getCacheKey(int $storeId):string
    {
        return self::CACHE_NAME . '_' . $storeId;
    }

    public function generateNewAccessKey(int $storeId)
    {
        /** @var \Webbhuset\CollectorCheckout\Config\StoreConfig $storeConfig */
        $storeConfig = $this->storeConfigFactory->create();
        $storeConfig->setScopeStoreId($storeId);
        $makeAccessKeyRequest = new GetAccessKey(
            $storeConfig
        );

        return $makeAccessKeyRequest->getAccessKey();
    }
}
