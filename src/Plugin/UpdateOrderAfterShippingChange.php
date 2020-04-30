<?php

namespace Webbhuset\CollectorCheckout\Plugin;

/**
 * Class UpdateOrderAfterShippingChange
 *
 * @package Webbhuset\CollectorCheckout\Plugin
 */
class UpdateOrderAfterShippingChange
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\QuoteHandler
     */
    protected $quoteDataHandler;
    /**
     * @var \Webbhuset\CollectorCheckout\Config\Config
     */
    protected $config;

    /**
     * UpdateOrderAfterShippingChange constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface         $quoteRepository
     * @param \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler
     * @param \Webbhuset\CollectorCheckout\Config\Config     $config
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorCheckout\Config\Config $config
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->config = $config;
    }

    /**
     * Plugin function to set a flag that collector bank needs update on address save
     *
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param callable                                              $proceed
     * @param mixed                                                 ...$args
     * @return mixed
     */
    public function aroundSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        callable $proceed,
        ...$args
    ) {
        $cartId = reset($args);
        $this->setNeedsCollectorUpdate($cartId);

        return $proceed(...$args);
    }

    /**
     * Sets a flag on the quote to indicate that the cart needs to be updated in collector bank
     *
     * @param $cartId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setNeedsCollectorUpdate($cartId)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        if (
            $this->quoteDataHandler->getPublicToken($quote)
            && $this->config->getIsActive($quote->getStoreId())
        ) {
            $quote->setNeedsCollectorUpdate(true);
        }
    }
}
