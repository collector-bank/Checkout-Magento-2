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
        \Webbhuset\CollectorCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorCheckout\QuoteUpdater $quoteUpdater,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession   = $checkoutSession;
        $this->collectorAdapter  = $collectorAdapter;
        $this->quoteConverter    = $quoteConverter;
        $this->quoteUpdater      = $quoteUpdater;
        $this->logger            = $logger;

        return parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $quote = $this->checkoutSession->getQuote(); // get id from session or url?

        $publicId = $this->getRequest()->getParam('quoteid');
        $eventName = $this->getRequest()->getParam('event');

        if (!$quote->getId()) {
            $result->setHttpResponseCode(404);
            $this->logger->addCritical(
                "Quote updater controller - Quote not found quoteId: $publicId event: $eventName"
            );
            return $result->setData(['message' => __('Quote not found')]);
        }

        $quote = $this->collectorAdapter->synchronize($quote, $eventName);
        $shippingAddress = $quote->getShippingAddress();

        $result->setData(
            [
                'postcode' => $shippingAddress->getPostcode(),
                'region' => $shippingAddress->getRegion(),
                'country_id' => $shippingAddress->getCountryId(),
                'shipping_method' => $shippingAddress->getShippingMethod(),
                'updated' => true
            ]
        );

        return $result;
    }
}
