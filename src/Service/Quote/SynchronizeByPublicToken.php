<?php

namespace Webbhuset\CollectorCheckout\Service\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Webbhuset\CollectorCheckout\Adapter;
use Webbhuset\CollectorCheckout\Checkout\Quote\Manager;

/**
 * Access synchronize SDK call with public token only
 */
class SynchronizeByPublicToken
{
    /**
     * @var Manager
     */
    private Manager $quoteManager;

    /**
     * @var Adapter
     */
    private Adapter $collectorAdapter;

    public function __construct(
        Manager $quoteManager,
        Adapter $collectorAdapter
    ) {
        $this->quoteManager = $quoteManager;
        $this->collectorAdapter = $collectorAdapter;
    }

    /**
     * @param string $publicToken
     * @param string|null $eventName
     * @return Quote|null
     */
    public function execute(string $publicToken, ?string $eventName = null): ?Quote
    {
        try {
            $quote = $this->quoteManager->getQuoteByPublicToken($publicToken);
            return $this->collectorAdapter->synchronize($quote, $eventName);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
