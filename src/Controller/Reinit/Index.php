<?php

namespace Webbhuset\CollectorCheckout\Controller\Reinit;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $checkoutSession;
    protected $logger;
    protected $quoteRepository;
    protected $quoteCollection;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\ResourceModel\Quote\Collection $quoteCollection
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession   = $checkoutSession;
        $this->logger            = $logger;
        $this->quoteRepository   = $quoteRepository;
        $this->quoteCollection   = $quoteCollection;

        return parent::__construct($context);
    }

    public function execute()
    {
        $publicId = $this->getRequest()->getParam('publicId');

        $quote = $this->quoteCollection->addFieldToFilter('collectorbank_public_id', $publicId)
            ->setOrder('entity_id', 'DESC')
            ->getFirstItem();

        if (!$quote->getId()) {

            $this->logger->critical(
                "Tried to reinitialize quote but could not find one with publicId: {$publicId}"
            );

            return $this->createResult(
                'Could not find quote',
                404,
                false
            );
        }

        $customerId = $this->checkoutSession->getCustomerId();
        $quote->setIsActive(1)->setReservedOrderId(null)
            ->setCustomerId($customerId);

        $this->quoteRepository->save($quote);
        $this->checkoutSession->replaceQuote($quote)
            ->unsLastRealOrderId();

        $this->logger->critical(
            "Restored quoteId: {$quote->getId()}, publicId: {$publicId}"
        );

        return $this->createResult(
            'Quote restored',
            200,
            true
        );
    }

    public function createResult($message, $httpResponseCode, $isReinited)
    {
        $jsonResult = $this->resultJsonFactory->create();

        $response = [
            'title' => __($message),
            'reinitialized' => $isReinited,
        ];
        $jsonResult->setHttpResponseCode($httpResponseCode);
        $jsonResult->setHeader("Content-Type", "application/json", true);
        $jsonResult->setData($response);

        return $jsonResult;
    }
}
