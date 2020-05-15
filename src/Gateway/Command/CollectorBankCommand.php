<?php

namespace Webbhuset\CollectorCheckout\Gateway\Command;

use Webbhuset\CollectorPaymentSDK\Errors\ResponseError as ResponseError;
use Magento\Payment\Gateway\CommandInterface as CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\TransactionInterface;

/**
 * Class CollectorBankCommand
 *
 * @package Webbhuset\CollectorCheckout\Gateway\Command
 */
class CollectorBankCommand implements CommandInterface
{
    /**
     * @var string
     */
    protected $method;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\PaymentHandlerFactory
     */
    protected $paymentHandler;
    /**
     * @var \Webbhuset\CollectorCheckout\Invoice\Administration
     */
    protected $invoice;
    /**
     * @var \Webbhuset\CollectorCheckout\Invoice\Transaction\ManagerFactory
     */
    protected $transaction;
    /**
     * @var \Webbhuset\CollectorCheckout\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Webbhuset\CollectorCheckout\Invoice\RowMatcher
     */
    protected $rowMatcher;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\OrderHandler
     */
    protected $orderHandler;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    protected $invoiceHandler;
    /**
     * CollectorBankCommand constructor.
     *
     * @param                                                                     $client
     * @param \Webbhuset\CollectorCheckout\Data\PaymentHandlerFactory         $paymentHandler
     * @param \Webbhuset\CollectorCheckout\Invoice\Administration             $invoice
     * @param \Webbhuset\CollectorCheckout\Invoice\Transaction\ManagerFactory $transaction
     * @param \Webbhuset\CollectorCheckout\Logger\Logger                      $logger
     */
    public function __construct(
        $client,
        \Webbhuset\CollectorCheckout\Data\PaymentHandlerFactory $paymentHandler,
        \Webbhuset\CollectorCheckout\Invoice\Administration $invoice,
        \Webbhuset\CollectorCheckout\Invoice\Transaction\ManagerFactory $transaction,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger,
        \Webbhuset\CollectorCheckout\Invoice\RowMatcher $rowMatcher,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Webbhuset\CollectorCheckout\Invoice\RowMatcher\InvoiceHandler $invoiceHandler
    ) {
        $this->method           = $client['method'];
        $this->paymentHandler   = $paymentHandler;
        $this->invoice          = $invoice;
        $this->transaction      = $transaction;
        $this->logger           = $logger;
        $this->rowMatcher       = $rowMatcher;
        $this->messageManager   = $messageManager;
        $this->orderHandler     = $orderHandler;
        $this->orderRepository  = $orderRepository;
        $this->invoiceHandler   = $invoiceHandler;
    }

    /**
     * @param array $commandSubject
     * @return \Magento\Payment\Gateway\Command\ResultInterface|void|null
     */
    public function execute(array $commandSubject)
    {
        $method = $this->method;
        if (method_exists($this, $method)) {
            call_user_func([$this, $method], $commandSubject);
        }
    }

    /**
     * Actives / captures the invoice for the order
     *
     * @param $data
     * @return bool
     */
    public function capture($data)
    {
        $payment    = SubjectReader::readPayment($data)->getPayment();
        $order      = $payment->getOrder();
        $invoice    = $order->getInvoiceCollection()->getLastItem();

        $articleList = $this->rowMatcher->invoiceToArticleList($invoice, $order);

        try {
            $invoiceNo  = $this->getPurchaseIdentifier($order);
            $orderId = $order->getId();

            if ($this->invoiceHandler->isDecimalRoundingInvoiced($order)) {
                $articleList->removeDecimalRounding();
            } else{
                $this->invoiceHandler->setDecimalRoundingIsInvoiced($order);
            }

            $response = $this->invoice->partActivateInvoice(
                $invoiceNo,
                $articleList,
                $orderId,
                $invoiceNo
            );

            $this->saveNewInvoiceNumber($order, $response);
            $this->addCaptureSuccessMessage($response);

            $this->transaction->create()->addTransaction(
                $payment->getOrder(),
                TransactionInterface::TYPE_CAPTURE,
                false,
                $response
            );

        } catch (ResponseError $e) {
            $incrementOrderId = (string)$payment->getOrder()->getIncrementId();
            $this->logger->addCritical(
                "Response error on capture. increment orderId: {$incrementOrderId} invoiceNo {$invoiceNo}" .
                $e->getMessage()
            );
            throw new \Webbhuset\CollectorCheckout\Exception\Exception(
                __($e->getMessage())
            );

            return false;
        }

        return true;
    }


    /**
     * Save collector invoice number on the order
     *
     * @param $order
     * @param $response
     */
    public function saveNewInvoiceNumber($order, $response)
    {
        if (isset($response['NewInvoiceNo'])) {
            $newInvoiceNo = $response['NewInvoiceNo'];
            $this->orderHandler->setInvoiceNumber($order, $newInvoiceNo);
            $this->orderRepository->save($order);
        }
    }


    /**
     * Get collector invoice number on an order
     *
     * @param $order
     * @return mixed|string|null
     */
    public function getPurchaseIdentifier($order)
    {
        $invoiceNumber = $this->orderHandler->getInvoiceNumber($order);
        if($invoiceNumber) {

            return $invoiceNumber;
        }
        $payment = $order->getPayment();

        return $this->paymentHandler->create()->getPurchaseIdentifier($payment);
    }


    /**
     * Add success messages to show in admin for capture invoice
     *
     * @param $response
     */
    public function addCaptureSuccessMessage($response)
    {
        $message = [];

        if (isset($response['TotalAmount'])) {
            $totalAmount = $response['TotalAmount'];
            $message[] = __('The invoice was activated for %1', $totalAmount);
        }
        if (isset($response['NewInvoiceNo'])) {
            $newInvoiceNo = $response['NewInvoiceNo'];
            $message[] = __('The new invoice number is: %1', $newInvoiceNo);
        }
        if (isset($response['InvoiceUrl'])) {
            $invoiceUrl = $response['InvoiceUrl'];
            $message[] = __('You can access the invoice using this link: %1', $invoiceUrl);
        }
        if (!empty($message)) {
            $messageText = implode(". ", $message);
            $this->messageManager->addSuccessMessage($messageText);
        }
    }

    /**
     * Authorizes the order and add transaction data
     *
     * @param $data
     * @return mixed
     */
    public function authorize($data)
    {
        $payment = $this->extractPayment($data);

        $this->transaction->create()->addTransaction(
            $payment->getOrder(),
            TransactionInterface::TYPE_AUTH
        );

        return $data;
    }

    /**
     * Refunds the order / payment
     *
     * @param $payment
     * @return bool
     */
    public function refund($payment)
    {
        $payment    = SubjectReader::readPayment($payment)->getPayment();
        $order      = $payment->getOrder();
        $creditMemo = $payment->getCreditmemo();

        $this->adjustInvoice($creditMemo, $payment);

        $articleList = $this->rowMatcher->creditMemoToArticleList($creditMemo, $order);

        if(count($articleList->getArticleList()) == 0){

            return true;
        }

        try {
            $invoiceNo = $creditMemo->getInvoice()->getTransactionId();
            $orderId = (int)$payment->getOrder()->getId();

            $response = $this->invoice->partCreditInvoice(
                $invoiceNo,
                $articleList,
                $orderId
            );

            $this->transaction->create()->addTransaction(
                $payment->getOrder(),
                TransactionInterface::TYPE_REFUND,
                $response
            );

        } catch (ResponseError $e) {
            $incrementOrderId = (int)$payment->getOrder()->getIncrementOrderId();
            $this->logger->addCritical(
                "Response error on refund increment orderId: {$incrementOrderId} invoiceNo {$invoiceNo}" .
                $e->getMessage()
            );

            return false;
        }

        return true;
    }

    /**
     * Adjust a collector invoice with negative or positive adjustment fees
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditMemo
     * @param                                       $payment
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Webbhuset\CollectorCheckout\Exception\Exception
     */
    protected function adjustInvoice(
        \Magento\Sales\Model\Order\Creditmemo $creditMemo,
        $payment
    ) {
        $invoiceRows = $this->getAdjustmentsInvoiceRows($creditMemo);

        if (count($invoiceRows) == 0) {

            return;
        }

        try {
            $invoiceNo = $creditMemo->getInvoice()->getTransactionId();
            $orderId = (int)$payment->getOrder()->getId();

            $response = $this->invoice->adjustInvoice(
                $invoiceNo,
                $invoiceRows,
                $orderId
            );

            $this->transaction->create()->addTransaction(
                $payment->getOrder(),
                TransactionInterface::TYPE_REFUND,
                $response
            );

        } catch (ResponseError $e) {
            $incrementOrderId = (int)$payment->getOrder()->getIncrementOrderId();
            $this->logger->addCritical(
                "Response error on adjustment of invoice: {$incrementOrderId} invoiceNo {$invoiceNo}" .
                $e->getMessage()
            );
            throw new \Webbhuset\CollectorCheckout\Exception\Exception(
                __($e->getMessage())
            );
        }
    }


    /**
     * Get adjustments as collector invoice rows
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditMemo
     * @return array
     */
    public function getAdjustmentsInvoiceRows(
        \Magento\Sales\Model\Order\Creditmemo $creditMemo
    ) {
        $invoiceRows = [];

        if (0 < $creditMemo->getBaseAdjustmentNegative()) {
            $adjustmentFee = $creditMemo->getBaseAdjustmentNegative();
            $invoiceRows[] = $this->rowMatcher->adjustmentToInvoiceRows($adjustmentFee)->toArray();
        }

        if (0 < $creditMemo->getBaseAdjustmentPositive()) {
            $adjustmentFee = (-1)*$creditMemo->getBaseAdjustmentPositive();
            $invoiceRows[] = $this->rowMatcher->adjustmentToInvoiceRows($adjustmentFee)->toArray();
        }

        return $invoiceRows;
    }


    /**
     * Void / cancel the order
     *
     * @param $payment
     * @return bool
     */
    public function void($payment)
    {
        $payment = $this->extractPayment($payment);
        $paymentHandler = $this->paymentHandler->create();

        $response = [];
        try {
            $invoiceNo = $paymentHandler->getPurchaseIdentifier($payment);
            $orderId = (int)$payment->getOrder()->getId();

            $response = $this->invoice->cancelInvoice(
                $invoiceNo,
                $orderId
            );
        } catch (ResponseError $e) {
            $incrementOrderId = (int)$payment->getOrder()->getIncrementOrderId();
            $this->logger->addCritical(
                "Response error on void / cancel increment orderId: {$incrementOrderId} invoiceNo; {$invoiceNo}" .
                $e->getMessage()
            );
            return false;
        }

        $this->transaction->create()->addTransaction(
            $payment->getOrder(),
            TransactionInterface::TYPE_VOID,
            true
        );

        return true;
    }

    /**
     * Void / cancel the order
     *
     * @param $payment
     * @return bool
     */
    public function cancel($payment)
    {
        return $this->void($payment);
    }

    /**
     * Extracts the payment information from the payment object
     *
     * @param $payment
     * @return \Magento\Payment\Model\InfoInterface
     */
    public function extractPayment($payment)
    {
        $payment = SubjectReader::readPayment($payment);

        return $payment->getPayment();
    }
}
