<?php

namespace Webbhuset\CollectorCheckout\Plugin;

/**
 * Class PostcodeReplacer
 *
 * @package Webbhuset\CollectorCheckout\Plugin
 */
class PostcodeReplacer
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
    protected $collectorAdapter;

    /**
     * PostcodeReplacer constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface         $quoteRepository
     * @param \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler
     * @param \Webbhuset\CollectorCheckout\Config\Config     $config
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorCheckout\Config\Config $config,
        \Webbhuset\CollectorCheckout\Adapter $collectorAdapter
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->config = $config;
        $this->collectorAdapter = $collectorAdapter;
    }

    /**
     *
     *
     * @param \Magento\Checkout\Model\ShippingInformationManagement   $subject
     * @param                                                         $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        if (
            $this->quoteDataHandler->getPublicToken($quote)
            && $this->config->getIsActive($quote->getStoreId())
        ) {
            $shippingAddress = $quote->getShippingAddress();

            $addressInformation->getShippingAddress()
                ->setPostcode($shippingAddress->getPostcode());
        }

        return [$cartId, $addressInformation];
    }

    public function afterCalculate($subject, $result, $cartId, \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        if (
            $this->quoteDataHandler->getPublicToken($quote)
            && $this->config->getIsActive($quote->getStoreId())
        ) {
            $quote->setNeedsCollectorUpdate(true);
            $this->collectorAdapter->synchronize($quote);
        }

        return $result;
    }

    /**
     * Sets address if available before calculating totals
     *
     * @param                                                       $subject
     * @param                                                       $cartId
     * @param \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeCalculate($subject, $cartId, \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation)
    {
        if ($addressInformation
            && $addressInformation->getAddress()
            && $addressInformation->getAddress()->getPostcode()) {

            return [$cartId, $addressInformation];
        }

        $quote = $this->quoteRepository->getActive($cartId);
        if (
            $this->quoteDataHandler->getPublicToken($quote)
            && $this->config->getIsActive($quote->getStoreId())
        ) {
            $shippingAddress = $quote->getShippingAddress();

            $addressInformation->getAddress()
                ->setPostcode($shippingAddress->getPostcode());
        }

        return [$cartId, $addressInformation];
    }
}