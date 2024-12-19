<?php

namespace Webbhuset\CollectorCheckout\Controller\Update;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorCheckout\Controller\Update
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Webbhuset\CollectorCheckout\Adapter
     */
    protected $collectorAdapter;
    /**
     * @var \Webbhuset\CollectorCheckout\QuoteConverter
     */
    protected $quoteConverter;
    /**
     * @var \Webbhuset\CollectorCheckout\QuoteUpdater
     */
    protected $quoteUpdater;
    /**
     * @var \Webbhuset\CollectorCheckout\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Webbhuset\CollectorCheckout\Checkout\Quote\Manager
     */
    protected $quoteManager;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context            $context
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Webbhuset\CollectorCheckout\Adapter         $collectorAdapter
     * @param \Webbhuset\CollectorCheckout\QuoteConverter  $quoteConverter
     * @param \Webbhuset\CollectorCheckout\QuoteUpdater    $quoteUpdater
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Webbhuset\CollectorCheckout\Logger\Logger   $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorCheckout\Checkout\Quote\Manager $quoteManager,
        \Webbhuset\CollectorCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorCheckout\QuoteUpdater $quoteUpdater,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger
    ) {
        parent::__construct($context);

        $this->quoteManager = $quoteManager;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession   = $checkoutSession;
        $this->collectorAdapter  = $collectorAdapter;
        $this->quoteConverter    = $quoteConverter;
        $this->quoteUpdater      = $quoteUpdater;
        $this->logger            = $logger;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $publicToken = $this->getRequest()->getParam('publicToken');
        $eventName = $this->getRequest()->getParam('event');
        $quote = $this->quoteManager->getQuoteByPublicToken($publicToken);

        if (!$quote->getId()) {
            $result->setHttpResponseCode(404);
            $this->logger->addCritical(
                "Quote updater controller - Quote not found quoteId: $publicToken event: $eventName"
            ,$this->getRequest());
            return $result->setData(['message' => __('Quote not found')]);
        }

        $quote = $this->collectorAdapter->synchronize($quote, $eventName);
        $shippingAddress = $quote->getShippingAddress();

        $data = [
            'postcode' => $shippingAddress->getPostcode(),
            'region' => $shippingAddress->getRegion(),
            'country_id' => $shippingAddress->getCountryId(),
            'shipping_method' => $shippingAddress->getShippingMethod(),
            'updated' => true
        ];
        if ($quote->getShippingAddress()->getShippingMethod() === 'collectorshipping_collectorshipping') {
            $data['carrier_title'] = __('Delivery');;
            $data['shipping_method_title'] = $shippingAddress->getShippingDescription();
            $result->setData($data);
            return $result;
        }

        $shippingMethod = $shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod());
        if ($shippingMethod && $shippingMethod->getRateId()) {
            $data['carrier_title'] = $shippingMethod->getCarrierTitle();
            $data['shipping_method_title'] = $shippingMethod->getMethodTitle();
        }
        $result->setData($data);

        return $result;
    }
}
