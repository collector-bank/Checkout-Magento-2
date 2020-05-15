<?php

namespace Webbhuset\CollectorCheckout\Invoice;

use Webbhuset\CollectorPaymentSDK\Adapter\SoapAdapter;
use Webbhuset\CollectorPaymentSDK\Invoice\Administration as InvoiceAdministration;

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
        \Webbhuset\CollectorCheckout\Invoice\Transaction\ManagerFactory $transaction,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger
    ) {
        $this->configFactory   = $configFactory;
        $this->invoiceService  = $invoiceService;
        $this->transaction     = $transaction;
        $this->logger          = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderHandler    = $orderHandler;
    }

    /**
     * Activate the invoice in collector bank portal
     *
     * @param string $invoiceNo
     * @param string $orderId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function activateInvoice(string $invoiceNo, string $orderId):array
    {
        $config = $this->getConfig($orderId);

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $result = $invoiceAdmin->activateInvoice($invoiceNo, $orderId);

        $this->logger->addInfo(
            "Invoice activated online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $result;
    }

    /**
     * Activate the invoice in collector bank portal
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

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice cancelled online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->cancelInvoice($invoiceNo, $orderId);
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

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice credited online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->partCreditInvoice($invoiceNo, $articleList, $orderId);
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

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice activated online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->partActivateInvoice($invoiceNo, $articleList, $correlationId);
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
    public function creditInvoice(string $invoiceNo, string $orderId):array
    {
        $config = $this->getConfig($orderId);

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice credited online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->creditInvoice($invoiceNo, $orderId);
    }


    /**
     * Adjust invoice in collector bank portal
     *
     * @param string $invoiceNo
     * @param \Webbhuset\CollectorPaymentSDK\Invoice\Rows\InvoiceRows $invoiceRows
     * @param string $orderId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function adjustInvoice(
        string $invoiceNo,
        $invoiceRows,
        string $orderId
    ):array {
        $config = $this->getConfig($orderId);

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice adjusted online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->adjustInvoice($invoiceNo, $invoiceRows, $orderId);
    }


    /**
     * Get invoice information from collector bank portal
     *
     * @param int    $invoiceNo
     * @param int    $orderId
     * @param string $clientIp
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getInvoiceInformation(int $invoiceNo, int $orderId, string $clientIp):array
    {
        $config = $this->getConfig($orderId);

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        return $invoiceAdmin->getInvoiceInformation($invoiceNo, $clientIp, $orderId);
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
