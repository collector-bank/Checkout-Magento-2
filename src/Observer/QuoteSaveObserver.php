<?php

namespace Webbhuset\CollectorCheckout\Observer;

/**
 * Class QuoteSaveObserver
 *
 * @package Webbhuset\CollectorCheckout\Observer
 */
class QuoteSaveObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Webbhuset\CollectorCheckout\Config\Config
     */
    protected $config;
    /**
     * @var \Webbhuset\CollectorCheckout\Adapter
     */
    protected $adapter;
    /**
     * @var \Webbhuset\CollectorCheckout\QuoteValidator
     */
    protected $quoteValidator;

    /**
     * QuoteSaveObserver constructor.
     *
     * @param \Webbhuset\CollectorCheckout\Config\Config  $config
     * @param \Webbhuset\CollectorCheckout\Adapter        $adapter
     * @param \Webbhuset\CollectorCheckout\QuoteValidator $quoteValidator
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Config\Config $config,
        \Webbhuset\CollectorCheckout\Adapter $adapter,
        \Webbhuset\CollectorCheckout\QuoteValidator $quoteValidator
    ) {
        $this->config = $config;
        $this->adapter = $adapter;
        $this->quoteValidator = $quoteValidator;
    }

    /**
     * On quote save, update collector order
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();

        if (
            !$quote->getIsActive()
            || !$quote->getNeedsCollectorUpdate()
            || !$this->config->getIsActive()
            || !$this->isInitialized($quote)
            || !$this->quoteValidator->canUseCheckout($quote)
        ) {
            return;
        }

        $this->adapter->updateCart($quote);
        $this->adapter->updateFees($quote);
    }

    /**
     * Returns true if the checkout has been initialized on the quote
     *
     * @param $quote
     * @return bool
     */
    protected function isInitialized($quote)
    {
        return null !== $quote->getCollectorbankPrivateId();
    }
}
