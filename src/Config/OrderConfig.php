<?php

namespace Webbhuset\CollectorCheckout\Config;


class OrderConfig extends \Webbhuset\CollectorCheckout\Config\Config
{
    protected $orderDataHandler;
    protected $order;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webbhuset\CollectorCheckout\Config\Source\Country\Country $countryData,
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderDataHandler,
        \Magento\Sales\Api\Data\OrderInterface $order,
        $magentoStoreId = null
    ) {
        $this->orderDataHandler = $orderDataHandler;
        $this->order = $order;
        $this->magentoStoreId = $magentoStoreId;

        parent::__construct($scopeConfig, $encryptor, $storeManager, $countryData);
    }

    protected function getOrder() : \Magento\Sales\Api\Data\OrderInterface
    {
        return $this->order;
    }

    public function getStoreId() : string
    {
        $storeId = $this->orderDataHandler->getStoreId($this->getOrder());

        if ($storeId) {
            return $storeId;
        }

        return parent::getStoreId();
    }
}
