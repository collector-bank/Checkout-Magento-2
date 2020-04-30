<?php

namespace Webbhuset\CollectorCheckout\Data;

/**
 * Class PaymentHandler
 *
 * @package Webbhuset\CollectorCheckout\Data
 */
class PaymentHandler
{
    /**
     * Get payment method title from payment object
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return mixed|string
     */
    public function getMethodTitle(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "method_title");
    }

    /**
     * Get payment method title from payment object
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return mixed|string
     */
    public function getPaymentName(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "payment_name");
    }

    /**
     * Get amount to pay
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return mixed|string
     */
    public function getAmountToPay(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "amount_to_pay");
    }

    /**
     * Get invoice delivery method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return mixed|string
     */
    public function getInvoiceDeliveryMethod(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "invoice_delivery_method");
    }

    /**
     * Get purchase identifier (invoice number)
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return mixed|string
     */
    public function getPurchaseIdentifier(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "purchase_identifier");
    }

    /**
     * The payment data
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return mixed|string
     */
    public function getResult(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "result");
    }

    protected function extractValue($array, $key)
    {
        if (!isset($array[$key])) {

            return "";
        }

        return $array[$key];
    }
}
