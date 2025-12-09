<?php

namespace Webbhuset\CollectorCheckout\Invoice;

use Webbhuset\CollectorCheckoutSDK\Adapter\CurlWithAccessKey;

/**
 * Class Administration
 *
 * @package Webbhuset\CollectorCheckout\Invoice
 */
class Administration
{
    /**
     * @var \Webbhuset\CollectorCheckout\Config\OrderConfigFactory
     */
    protected $configFactory;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;
    /**
     * @var Transaction\ManagerFactory
     */
    protected $transaction;
    /**
     * @var \Webbhuset\CollectorCheckout\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\OrderHandler
     */
    protected $orderHandler;
    private \Webbhuset\CollectorCheckout\Adapter $adapter;
    private \Webbhuset\CollectorCheckout\Data\ExtractWalleyOrderId $extractWalleyOrderId;
    /**
     * @var RowMatcher
     */
    private RowMatcher $rowMatcher;

    /**
     * Administration constructor.
     *
     * @param \Webbhuset\CollectorCheckout\Config\OrderConfigFactory $config
     * @param \Magento\Sales\Model\Service\InvoiceService           $invoiceService
     * @param Transaction\ManagerFactory                            $transaction
     * @param \Magento\Sales\Model\OrderRepository                  $orderRepository
     * @param \Webbhuset\CollectorCheckout\Data\OrderHandler    $orderHandler
     * @param \Webbhuset\CollectorCheckout\Logger\Logger        $logger
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Config\OrderConfigFactory $configFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Webbhuset\CollectorCheckout\Adapter $adapter,
        \Webbhuset\CollectorCheckout\Invoice\Transaction\ManagerFactory $transaction,
        \Webbhuset\CollectorCheckout\Data\ExtractWalleyOrderId $extractWalleyOrderId,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Webbhuset\CollectorCheckout\Invoice\RowMatcher $rowMatcher,
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger
    ) {
        $this->configFactory   = $configFactory;
        $this->invoiceService  = $invoiceService;
        $this->transaction     = $transaction;
        $this->logger          = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderHandler    = $orderHandler;
        $this->adapter = $adapter;
        $this->extractWalleyOrderId = $extractWalleyOrderId;
        $this->rowMatcher = $rowMatcher;
    }

    /**
     * Cancel the invoice in collector bank portal
     *
     * @param string $invoiceNo
     * @param string $orderId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function cancelInvoice(string $invoiceNo, string $orderId):array
    {
        $config = $this->getConfig($orderId);

        /** @var \Webbhuset\CollectorCheckoutSDK\Adapter\CurlWithAccessKey $adapter */
        $adapter = $this->adapter->getAdapter($config);
        $walleyOrderId = $this->extractWalleyOrderId->execute((int)$orderId);
        $order = $this->orderRepository->get($orderId);
        $articleList = $this->rowMatcher->checkoutDataToArticleList($order);
        $uniqid = uniqid();
        $adapter->cancelInvoice($walleyOrderId, $articleList, $uniqid);

        $this->logger->addInfo(
            "Invoice cancelled online orderId: {$orderId} invoiceNo: {$walleyOrderId} "
        );

        return ['NewInvoiceNo' => $uniqid];
    }

    /**
     * Credit an invoice in collector bank portal
     *
     * @param string $invoiceNo
     * @param string $orderId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function partCreditInvoice(
        string $invoiceNo,
        \Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList $articleList,
        string $orderId
    ):array {
        $config = $this->getConfig($orderId);

        /** @var \Webbhuset\CollectorCheckoutSDK\Adapter\CurlWithAccessKey $adapter */
        $adapter = $this->adapter->getAdapter($config);
        $walleyOrderId = $this->extractWalleyOrderId->execute((int)$orderId);
        $uniqid = uniqid();
        $adapter->partCreditInvoice($walleyOrderId, $articleList, $uniqid);
        $this->logger->addInfo(
            "Invoice credited online orderId: {$orderId} invoiceNo: {$walleyOrderId} "
        );

        return ['NewInvoiceNo' => $uniqid];
    }


    /**
     * Credit an invoice in collector bank portal
     *
     * @param string $invoiceNo
     * @param string $orderId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function partActivateInvoice(
        string $invoiceNo,
        \Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList $articleList,
        string $orderId,
        string $correlationId
    ):array {
        $config = $this->getConfig($orderId);

        /** @var \Webbhuset\CollectorCheckoutSDK\Adapter\CurlWithAccessKey $adapter */
        $adapter = $this->adapter->getAdapter($config);
        $walleyOrderId = $this->extractWalleyOrderId->execute((int)$orderId);
        $uniq = uniqid();
        $adapter->partActivateInvoice($walleyOrderId, $articleList, $uniq);

        $this->logger->addInfo(
            "Invoice activated online orderId: {$orderId} invoiceNo: {$walleyOrderId} "
        );

        return ['NewInvoiceNo' => $uniq];
    }


    /**
     * Invoice an order offline
     *
     * @param \Magento\Sales\Model\Order $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function invoiceOrderOffline(
        \Magento\Sales\Model\Order $order
    ) {
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $this->logger->addInfo(
            "Invoice order offline orderId: {$order->getIncrementId()} qouteId: {$order->getQuoteId()} "
        );

        $this->transaction->create()->addInvoiceTransaction($invoice);
    }

    /**
     * Get order config
     *
     * @param string                                         $orderId
     * @return \Webbhuset\CollectorCheckout\Config\OrderConfig
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getConfig(
        string $orderId
    ) {
        $order = $this->orderRepository->get($orderId);
        $magentoStoreId = $order->getStoreId();
        $config = $this->configFactory->create(['order' => $order, 'magentoStoreId' => $magentoStoreId]);

        return $config;
    }
}
