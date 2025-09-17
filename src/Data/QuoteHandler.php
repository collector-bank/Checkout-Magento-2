<?php

namespace Webbhuset\CollectorCheckout\Data;

use Magento\Quote\Api\Data\CartInterface as Quote;

/**
 * Class QuoteHandler
 *
 * @package Webbhuset\CollectorCheckout\Data
 */
class QuoteHandler
{
    /**
     * Get private id (=private token) from quote
     *
     * @param Quote $quote
     * @return mixed
     */
    public function getPrivateId(Quote $quote)
    {
        return $quote->getCollectorbankPrivateId();
    }

    /**
     * Set private id on quote
     *
     * @param Quote $quote
     * @param       $id
     * @return $this
     */
    public function setPrivateId(Quote $quote, $id)
    {
        $quote->setCollectorbankPrivateId($id);

        return $this;
    }

    /**
     * Get public token on quote
     *
     * @param Quote $quote
     * @return mixed
     */
    public function getPublicToken(Quote $quote)
    {
        return $quote->getCollectorbankPublicId();
    }

    /**
     * Set public token on quote
     *
     * @param Quote $quote
     * @param       $id
     * @return $this
     */
    public function setPublicToken(Quote $quote, $id)
    {
        $quote->setCollectorbankPublicId($id);

        return $this;
    }

    /**
     * Get customer type
     *
     * @param Quote $quote
     * @return mixed
     */
    public function getCustomerType(Quote $quote)
    {
        return $quote->getCollectorbankCustomerType();
    }

    /**
     * Set customer type on quote
     *
     * @param Quote $quote
     * @param       $customerType
     * @return $this
     */
    public function setCustomerType(Quote $quote, $customerType)
    {
        $quote->setCollectorbankCustomerType($customerType);

        return $this;
    }

    /**
     * Get collector bank data from quote
     *
     * @param Quote $quote
     * @return array
     */
    public function getData(Quote $quote)
    {
        $collectorData = $quote->getCollectorbankData();
        if (!$collectorData) {
            return [];
        }
        $data = json_decode($collectorData);

        return ($data) ? get_object_vars($data) : [];
    }

    /**
     * Set collector bank data on quote
     *
     * @param Quote $quote
     * @param       $data
     * @return $this
     */
    public function setData(Quote $quote, $data)
    {
        $quote->setCollectorbankData(json_encode($data));

        return $this;
    }

    /**
     * Set org number on quote
     *
     * @param Quote $quote
     * @param       $orgNumber
     * @return QuoteHandler
     */
    public function setOrgNumber(Quote $quote, $orgNumber)
    {
        return $this->setAdditionalData($quote, 'org_number', $orgNumber);
    }

    /**
     * Set payment method
     *
     * @param Quote $quote
     * @param       $paymentMethod
     * @return QuoteHandler
     */
    public function setPaymentMethod(Quote $quote, $paymentMethod)
    {
        return $this->setAdditionalData($quote, 'payment_method', $paymentMethod);
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param                                       $ssn
     *
     * @return \Webbhuset\CollectorCheckout\Data\QuoteHandler
     */
    public function setNationalIdentificationNumber(Quote $quote, $ssn)
    {
        return $this->setAdditionalData($quote, 'national_identification_number', $ssn);
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     */
    public function getNationalIdentificationNumber(Quote $quote, $order)
    {
        return $this->getAdditionalData($quote, 'national_identification_number');
    }


    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     */
    public function getPaymentMethod(Quote $quote, $order)
    {
        return $this->getAdditionalData($quote, 'payment_method');
    }

    /**
     * Get org number from quote
     *
     * @param Quote $quote
     * @return mixed|null
     */
    public function getOrgNumber(Quote $quote)
    {
        return $this->getAdditionalData($quote, 'org_number');
    }

    /**
     * Set reference on quote
     *
     * @param Quote $quote
     * @param       $reference
     * @return QuoteHandler
     */
    public function setReference(Quote $quote, $reference)
    {
        return $this->setAdditionalData($quote, 'reference', $reference);
    }

    /**
     * Get reference from quote
     *
     * @param Quote $quote
     * @return mixed|null
     */
    public function getReference(Quote $quote)
    {
        return $this->getAdditionalData($quote, 'reference');
    }


    /**
     * Set invoice tag on quote
     *
     * @param Quote $quote
     * @param       $reference
     * @return QuoteHandler
     */
    public function setInvoiceTag(Quote $quote, $reference)
    {
        return $this->setAdditionalData($quote, 'invoiceTag', $reference);
    }

    /**
     * Get invoice tag from quote
     *
     * @param Quote $quote
     * @return mixed|null
     */
    public function getInvoiceTag(Quote $quote)
    {
        return $this->getAdditionalData($quote, 'invoiceTag');
    }


    /**
     * Set collector bank store id on quote
     *
     * @param Quote $quote
     * @param       $reference
     * @return QuoteHandler
     */
    public function setStoreId(Quote $quote, $reference)
    {
        return $this->setAdditionalData($quote, 'store_id', $reference);
    }

    /**
     * Get store id from quote
     *
     * @param Quote $quote
     * @return mixed|null
     */
    public function getStoreId(Quote $quote)
    {
        return $this->getAdditionalData($quote, 'store_id');
    }


    /**
     * Set delivery checkout data on quote
     *
     * @param Quote $quote
     * @param       $shippingInfo
     * @return QuoteHandler
     */
    public function setDeliveryCheckoutData(Quote $quote, $shippingInfo)
    {
        return $this->setAdditionalData($quote, 'delivery_checkout_data', json_encode($shippingInfo));
    }

    /**
     * Get delivery checkout data from quote
     *
     * @param Quote $quote
     * @return array|null
     */
    public function getDeliveryCheckoutData(Quote $quote)
    {
        $shippingData = $this->getAdditionalData($quote, 'delivery_checkout_data');
        if (!$shippingData) {
            return [];
        }
        $shippingData = json_decode($shippingData);

        return ($shippingData) ? get_object_vars($shippingData) : [];
    }


    /**
     * Set newsletter subscribe on quote (subscribes the customer on order place)
     *
     * @param Quote $quote
     * @param int   $subscribe
     * @return QuoteHandler
     */
    public function setNewsletterSubscribe(Quote $quote, int $subscribe)
    {
        return $this->setAdditionalData($quote, 'newsletter_subscribe', $subscribe);
    }

    /**
     * Get newsletter subscribe
     *
     * @param Quote $quote
     * @return bool
     */
    public function getNewsletterSubscribe(Quote $quote):bool
    {
        return $this->getAdditionalData($quote, 'newsletter_subscribe');
    }

    protected function getAdditionalData(Quote $quote, string $name)
    {
        $data = $this->getData($quote);
        if (!isset($data[$name])) {
            return null;
        }

        return $data[$name];
    }

    protected function setAdditionalData(Quote $quote, string $name, string $value)
    {
        $data = $this->getData($quote);
        $data[$name] = $value;

        return $this->setData($quote, $data);
    }
}
