<?php

namespace Webbhuset\CollectorCheckout\Controller\Success;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Webbhuset\CollectorCheckout\Adapter;
use Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory;
use Webbhuset\CollectorCheckout\Config\Config;
use Webbhuset\CollectorCheckout\Data\OrderHandlerFactory;
use Webbhuset\CollectorCheckout\Logger\Logger;
use Webbhuset\CollectorCheckoutSDK\Config\IframeConfig;
use Webbhuset\CollectorCheckoutSDK\Iframe;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorCheckout\Controller\Success
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var Adapter
     */
    protected $collectorAdapter;

    /**
     * @var ManagerFactory
     */
    protected $orderManager;

    /**
     * @var OrderHandlerFactory
     */
    protected $orderDataHandler;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Index constructor.
     *
     * @param Context                 $context
     * @param Adapter                 $collectorAdapter
     * @param ManagerFactory          $orderManager
     * @param OrderHandlerFactory     $orderDataHandler
     * @param PageFactory             $pageFactory
     * @param Logger                  $logger
     * @param Config                   $config
     * @param CartRepositoryInterface $quoteRepository
     * @param CheckoutSession         $checkoutSession
     */
    public function __construct(
        Context $context,
        Adapter $collectorAdapter,
        ManagerFactory $orderManager,
        OrderHandlerFactory $orderDataHandler,
        PageFactory $pageFactory,
        Logger $logger,
        Config $config,
        CartRepositoryInterface $quoteRepository,
        CheckoutSession $checkoutSession
    ) {
        $this->pageFactory      = $pageFactory;
        $this->collectorAdapter = $collectorAdapter;
        $this->orderManager     = $orderManager;
        $this->orderDataHandler = $orderDataHandler;
        $this->logger           = $logger;
        $this->config           = $config;
        $this->quoteRepository  = $quoteRepository;
        $this->checkoutSession  = $checkoutSession;

        parent::__construct($context);
    }

    /**
     * Execute success page controller action
     *
     * Loads the order by the reference token from the URL, updates the checkout
     * session with order information, and renders the success page with the
     * Collector iframe.
     *
     * @return ResponseInterface|ResultInterface|Page
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

            $orderId = $order->getId();
            $incrementOrderId = $order->getIncrementId();

            if (!$this->checkoutSession->getLastOrderId()) {
                $this->checkoutSession
                    ->setLastQuoteId($quoteId)
                    ->setLastSuccessQuoteId($quoteId)
                    ->setLastOrderId($orderId)
                    ->setLastRealOrderId($incrementOrderId)
                    ->setLastOrderStatus($order->getStatus());
            }
        } catch (NoSuchEntityException $e) {
            $page->getLayout()
                ->getBlock('collectorbank_success_iframe');
            $this->logger->addCritical(
                "Failed to load success page - Could not open order by publicToken: $reference. "
                . $e->getMessage()
            );
            return $page;
        }

        $orderDataHandler = $this->orderDataHandler->create();
        $publicToken = $orderDataHandler->getPublicToken($order);

        $iframeConfig = new IframeConfig(
            $publicToken,
            $this->config->getStyleDataLang(),
            $this->config->getStyleDataPadding(),
            $this->config->getStyleDataContainerId(),
            $this->config->getStyleDataActionColor(),
            $this->config->getStyleDataActionTextColor()
        );
        $iframe = Iframe::getScript($iframeConfig, $this->config->getMode());

        $page->getLayout()
            ->getBlock('collectorbank_success_iframe')
            ->setIframe($iframe)
            ->setSuccessOrder($order);

        return $page;
    }
}
