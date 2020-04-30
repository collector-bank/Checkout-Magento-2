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
        \Webbhuset\CollectorCheckout\Logger\Logger $logger
    ) {
        $this->method         = $client['method'];
        $this->paymentHandler = $paymentHandler;
        $this->invoice        = $invoice;
        $this->transaction    = $transaction;
        $this->logger         = $logger;
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
        $payment = $this->extractPayment($data);

        $paymentHandler = $this->paymentHandler->create();

        try {
            $invoiceNo = $paymentHandler->getPurchaseIdentifier($payment);
            $orderId = $payment->getOrder()->getId();

            $response = $this->invoice->activateInvoice(
                $invoiceNo,
                $orderId
            );
        } catch (ResponseError $e) {
            $incrementOrderId = (string)$payment->getOrder()->getIncrementId();
            $this->logger->addCritical(
                "Response error on capture. increment orderId: {$incrementOrderId} invoiceNo {$invoiceNo}" .
                $e->getMessage()
            );

            return false;
        }

        $this->transaction->create()->addTransaction(
            $payment->getOrder(),
            TransactionInterface::TYPE_CAPTURE
        );

        return true;
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
        $payment = $this->extractPayment($payment);
        $paymentHandler = $this->paymentHandler->create();

        try {
            $invoiceNo = $paymentHandler->getPurchaseIdentifier($payment);
            $orderId = (int)$payment->getOrder()->getId();

            $this->invoice->creditInvoice(
                $invoiceNo,
                $orderId
            );
        } catch (ResponseError $e) {
            $incrementOrderId = (int)$payment->getOrder()->getIncrementOrderId();
            $this->logger->addCritical(
                "Response error on refund increment orderId: {$incrementOrderId} invoiceNo {$invoiceNo}" .
                $e->getMessage()
            );

            return false;
        }
        $this->transaction->create()->addTransaction(
            $payment->getOrder(),
            TransactionInterface::TYPE_REFUND,
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
