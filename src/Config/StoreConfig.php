<?php

namespace Webbhuset\CollectorCheckout\Config;


class StoreConfig extends \Webbhuset\CollectorCheckout\Config\Config
{
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webbhuset\CollectorCheckout\Config\Source\Country\Country $countryData,
        \Webbhuset\CollectorCheckout\Oath\AccessKeyManager $accessKeyManager
    ) {
        parent::__construct($scopeConfig, $encryptor, $storeManager, $countryData, $accessKeyManager);
    }

    public function setScopeStoreId($storeId)
    {
        $this->magentoStoreId = $storeId;
    }

    public function getScopeStoreId() : string
    {
        return $this->magentoStoreId;
    }

    protected function getConfigValue($name)
    {
        $storeId = $this->getScopeStoreId();

        return $this->scopeConfig->getValue(
            'payment/collectorbank_checkout/configuration/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
