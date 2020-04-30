<?php

namespace Webbhuset\CollectorCheckout;

use Magento\Framework\Phrase;
use Webbhuset\CollectorCheckout\Exception\QuoteNotInSyncException;

class QuoteComparer
{
    protected $adapter;
    protected $quoteConverter;
    protected $config;
    protected $storeManager;

    public function __construct(
        \Webbhuset\CollectorCheckout\AdapterFactory $adapter,
        \Webbhuset\CollectorCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorCheckout\Config\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->adapter        = $adapter;
        $this->quoteConverter = $quoteConverter;
        $this->config         = $config;
        $this->storeManager   = $storeManager;
    }

    public function isQuoteInSync(
        \Magento\Quote\Api\Data\CartInterface $quote
    ): bool {
        $adapter = $this->adapter->create();
        $checkoutData = $adapter->acquireCheckoutInformationFromQuote($quote);

        $grandTotalInSync = $this->isGrandTotalSync($quote, $checkoutData);
        if (!$grandTotalInSync) {
            throw new QuoteNotInSyncException(new Phrase('Grand total not in sync'));
        }

        $cartInSync = $this->isCartItemsInSync($quote, $checkoutData);
        if (!$cartInSync) {
            throw new QuoteNotInSyncException(new Phrase('Items not in sync'));
        }

        return true;
    }

    public function isGrandTotalSync(
        \Magento\Quote\Model\Quote $quote,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) {
        $grandTotalCeil = ceil($quote->getGrandTotal());
        $collectorTotalCeil = ceil($this->calculateCollectorTotal($checkoutData));

        $grandTotalRound = round($quote->getGrandTotal());
        $collectorTotalRound = round($this->calculateCollectorTotal($checkoutData));

        return ($grandTotalCeil == $collectorTotalCeil)
            || ($grandTotalRound == $collectorTotalRound);
    }

    public function isCartItemsInSync(
        \Magento\Quote\Model\Quote $quote,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) {
        $collectorCartItems = $this->getCollectorCartAsArray($checkoutData);
        $cartItems = $this->getQuoteItemsAsArray($quote);

        array_walk($collectorCartItems, [$this, 'serializeElements']);
        array_walk($cartItems, [$this, 'serializeElements']);

        return empty(array_diff($collectorCartItems, $cartItems));
    }

    public function isCurrencyMatching()
    {
        $collectorCurrency = $this->config->getCurrency();
        $storeCurrency = $this->storeManager->getStore()->getCurrentCurrencyCode();

        return ($collectorCurrency == $storeCurrency);
    }

    protected function getQuoteItemsAsArray(
        \Magento\Quote\Model\Quote $quote
    ) {
        $cartItems = $this->quoteConverter->getCart($quote)->toArray();
        $cartItems = $cartItems['items'];

        array_walk($cartItems, [$this, 'removeExtraColumns']);
        array_walk($cartItems, [$this, 'trimIdField']);

        return $cartItems;
    }

    protected function getCollectorCartAsArray(
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) {
        $checkoutItems = $checkoutData->getCart()->getItems();

        array_walk($checkoutItems, [$this, 'toArrayOnElements']);
        array_walk($checkoutItems, [$this, 'removeExtraColumns']);

        return $checkoutItems;
    }

    protected function getCollectorFeesAsArray(
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) {
        if (!$checkoutData->getFees()) {
            return [];
        }
        $checkoutItems = $checkoutData->getFees()->toArray();

        array_walk($checkoutItems, [$this, 'removeExtraColumns']);

        return $checkoutItems;
    }

    protected function calculateCollectorTotal(
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) {
        $cartTotal = $this->calculateCollectorCartTotal($checkoutData);
        $feesTotal = $this->calculateCollectorFeesTotal($checkoutData);

        return $cartTotal + $feesTotal;
    }

    protected function calculateCollectorCartTotal(
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) {
        $cartItems = $this->getCollectorCartAsArray($checkoutData);

        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['unitPrice'] * $item['quantity'];
        }

        return $total;
    }

    protected function calculateCollectorFeesTotal(
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) {
        $cartItems = $this->getCollectorFeesAsArray($checkoutData);

        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['unitPrice'];
        }

        return $total;
    }

    protected function trimIdField(&$item, $key)
    {
        $item['id'] = trim($item['id']);
    }

    protected function serializeElements(&$item, $key)
    {
        $item = serialize($item);
    }

    protected function removeExtraColumns(&$item, $key)
    {
        unset($item['requiresElectronicId'], $item['sku'], $item['description']);
    }

    protected function toArrayOnElements(&$item, $key)
    {
        $item = $item->toArray();
    }
}
