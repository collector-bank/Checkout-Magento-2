<?php

namespace Webbhuset\CollectorCheckout\Observer;

use Magento\Framework\Exception\LocalizedException;
use Webbhuset\CollectorCheckout\Gateway\Config;

/**
 * Class SaveOrderBeforeSalesModelQuoteObserver
 *
 * @package Webbhuset\CollectorCheckout\Observer
 */
class SaveOrderBeforeSalesModelQuoteObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $objectCopyService;

    /**
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     */
    public function __construct(
        \Magento\Framework\DataObject\Copy $objectCopyService
    ) {
        $this->objectCopyService = $objectCopyService;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return SaveOrderBeforeSalesModelQuoteObserver
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        /* @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        $this->objectCopyService->copyFieldsetToTarget('sales_convert_quote', 'to_order', $quote, $order);
        $this->assertWalleyOrderExists($quote, $order);

        return $this;
    }

    /**
     * Block placement of Walley orders that were not created through Walley Checkout
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Sales\Model\Order $order
     * @throws LocalizedException
     */
    protected function assertWalleyOrderExists($quote, $order): void
    {
        $payment = $quote->getPayment();
        if (!$payment || $payment->getMethod() !== Config::CHECKOUT_CODE) {
            return;
        }

        $publicId = $order->getCollectorbankPublicId();
        if (!is_string($publicId) || trim($publicId) === '') {
            throw new LocalizedException(
                __('The order could not be placed because no Walley Checkout order exists.')
            );
        }
    }
}
