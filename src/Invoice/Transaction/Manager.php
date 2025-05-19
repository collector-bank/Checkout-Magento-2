<?php

namespace Webbhuset\CollectorCheckout\Invoice\Transaction;

class Manager
{
    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * Manager constructor.
     *
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory    $transactionFactory
     */
    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory
    ) {
        $this->invoiceService        = $invoiceService;
        $this->transactionFactory    = $transactionFactory;
    }

    /**
     * Adds a transaction to the order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param                                        $type
     * @param bool                                   $status
     */
    public function addTransaction(
        \Magento\Sales\Api\Data\OrderInterface $order,
	    $type,
	    $status = false,
        $response = []
    ) {
        $payment = $order->getPayment();

        $id             = $order->getIncrementId();
        $txnId          = "{$id}-{$type}";
        $parentTransId  = $payment->getLastTransId();
        $paymentData    = $payment->getAdditionalInformation();

        if (!empty($response)) {
            if(isset($response['InvoiceUrl'])) {
                $paymentData['invoice_url'] = $response['InvoiceUrl'];
            }
            if(isset($response['CorrelationId'])) {
                $purchaseIdentifier = $response['CorrelationId'];
                $paymentData['purchase_identifier'] = $purchaseIdentifier;
                $txnId = $purchaseIdentifier;
            }
            if(isset($response['TotalAmount'])) {
                $paymentData['amount_to_pay'] = $response['TotalAmount'];
            }
        }

        $payment->setTransactionId($txnId)
            ->setIsTransactionClosed($status)
            ->setTransactionAdditionalInfo(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $paymentData
            );

        $transaction = $payment->addTransaction($type, null, true);

        if ($parentTransId) {
            $transaction->setParentTxnId($parentTransId);
        }
        $payment->save();
    }

    /**
     * Adds an invoice to the transaction / order
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @throws \Exception
     */
    public function addInvoiceTransaction(
        \Magento\Sales\Model\Order\Invoice $invoice
    ) {
        $transaction = $this->transactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        $transaction->save();
    }
}
