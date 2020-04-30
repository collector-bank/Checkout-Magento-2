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
        $status = false
    ) {
        $payment = $order->getPayment();

        $id            = $order->getIncrementId();
        $txnId         = "{$id}-{$type}";
        $parentTransId = $payment->getLastTransId();
        $payment->setTransactionId($txnId)
            ->setIsTransactionClosed($status)
            ->setTransactionAdditionalInfo(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $payment->getAdditionalInformation()
            );

        $transaction = $payment->addTransaction($type, null, true);

        if ($parentTransId) {
            $transaction->setParentTxnId($parentTransId);
        }
        $transaction->save();
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
