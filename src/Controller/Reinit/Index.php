<?php

namespace Webbhuset\CollectorCheckout\Controller\Reinit;

use Magento\Framework\Exception\NoSuchEntityException;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $checkoutSession;
    protected $logger;
    protected $quoteRepository;
    protected $quoteCollection;
    /**
     * @var \Webbhuset\CollectorCheckout\Config\OrderConfig
     */
    private $orderConfig;
    /**
     * @var \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory
     */
    private $orderManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger,
        \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Webbhuset\CollectorCheckout\Config\OrderConfig $orderConfig,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\ResourceModel\Quote\Collection $quoteCollection
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession   = $checkoutSession;
        $this->logger            = $logger;
        $this->quoteRepository   = $quoteRepository;
        $this->quoteCollection   = $quoteCollection;
        $this->orderConfig       = $orderConfig;
        $this->orderManager      = $orderManager;
    }

    public function execute()
    {
        $publicId = $this->getRequest()->getParam('publicId');

        $quote = $this->quoteCollection->addFieldToFilter('collectorbank_public_id', $publicId)
            ->setOrder('entity_id', 'DESC')
            ->getFirstItem();

        if (!$quote->getId()) {
            return $this->createResult(
                'Could not find quote',
                404,
                false
            );
        }

        try {
            $order = $this->orderManager->create()->getOrderByPublicToken($publicId);
            $acknowledged  = $this->orderConfig->getOrderStatusAcknowledged();
            if ($order->getStatus() == $acknowledged) {
                return $this->createResult(
                    'Quote not restored',
                    200,
                    false
                );
            }
        } catch (NoSuchEntityException $e) {

        }
        if ($quote->getIsActive()) {
            return $this->createResult(
                'Quote not restored',
                200,
                true
            );
        }

        $quote->setIsActive(1)->setReservedOrderId(null);

        $this->quoteRepository->save($quote);
        $this->checkoutSession->replaceQuote($quote)
            ->unsLastRealOrderId();

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
