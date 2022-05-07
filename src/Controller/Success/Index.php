<?php

namespace Webbhuset\CollectorCheckout\Controller\Success;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorCheckout\Controller\Success
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;
    /**
     * @var
     */
    protected $checkoutSession;
    /**
     * @var \Webbhuset\CollectorCheckout\Adapter
     */
    protected $collectorAdapter;
    /**
     * @var \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory
     */
    protected $orderManager;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\OrderHandlerFactory
     */
    protected $orderDataHandler;
    /**
     * @var \Webbhuset\CollectorCheckout\Logger\Logger
     */
    protected $logger;

    protected $config;
    protected $quoteRepository;
    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context                          $context
     * @param \Webbhuset\CollectorCheckout\Adapter                       $collectorAdapter
     * @param \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager
     * @param \Webbhuset\CollectorCheckout\Data\OrderHandlerFactory      $orderDataHandler
     * @param \Magento\Framework\View\Result\PageFactory                     $pageFactory
     * @param \Webbhuset\CollectorCheckout\Logger\Logger                 $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Webbhuset\CollectorCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Webbhuset\CollectorCheckout\Data\OrderHandlerFactory $orderDataHandler,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger,
        \Webbhuset\CollectorCheckout\Config\Config $config,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->pageFactory      = $pageFactory;
        $this->collectorAdapter = $collectorAdapter;
        $this->orderManager     = $orderManager;
        $this->orderDataHandler = $orderDataHandler;
        $this->logger           = $logger;
        $this->config           = $config;
        $this->quoteRepository  = $quoteRepository;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $reference = $this->getRequest()->getParam('reference');
        $orderManager = $this->orderManager->create();

        $page = $this->pageFactory->create();
        try {
            $order = $orderManager->getOrderByPublicToken($reference);
            $quoteId = $order->getQuoteId();
            $quote = $this->quoteRepository->get($quoteId);
            $quote->setIsActive(0);
            $this->quoteRepository->save($quote);

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $page->getLayout()
                ->getBlock('collectorbank_success_iframe');
            $this->logger->critical(
                "Failed to load success page - Could not open order by publicToken: $reference. "
                . $e->getMessage()
            );
            return $page;
        }

        $orderDataHandler = $this->orderDataHandler->create();
        $publicToken = $orderDataHandler->getPublicToken($order);

        $iframeConfig = new \Webbhuset\CollectorCheckoutSDK\Config\IframeConfig(
            $publicToken
        );
        $iframe = \Webbhuset\CollectorCheckoutSDK\Iframe::getScript($iframeConfig, $this->config->getMode());

        $page->getLayout()
            ->getBlock('collectorbank_success_iframe')
            ->setIframe($iframe)
            ->setSuccessOrder($order);

        return $page;
    }
}
