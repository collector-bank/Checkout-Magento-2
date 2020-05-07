<?php

namespace Webbhuset\CollectorCheckout\Data;

use Magento\Sales\Api\Data\OrderInterface as Order;

/**
 * Class OrderHandler
 *
 * @package Webbhuset\CollectorCheckout\Data
 */
class OrderHandler
{
    /**
     * Get private from order (private token)
     *
     * @param Order $order
     * @return mixed
     */
    public function getPrivateId(Order $order)
    {
        return $order->getCollectorbankPrivateId();
    }

    /**
     * Set private on order (private token)
     *
     * @param Order $order
     * @param       $id
     * @return $this
     */
    public function setPrivateId(Order $order, $id)
    {
        $order->setCollectorbankPrivateId($id);

        return $this;
    }

    /**
     * Get public token from order (public token)
     *
     * @param Order $order
     * @return mixed
     */
    public function getPublicToken(Order $order)
    {
        return $order->getCollectorbankPublicId();
    }

    /**
     * Set public token on order (public token)
     *
     * @param Order $order
     * @param       $id
     * @return $this
     */
    public function setPublicToken(Order $order, $id)
    {
        $order->setCollectorbankPublicId($id);

        return $this;
    }

    /**
     * Get customer type on order (whether business or private)
     *
     * @param Order $order
     * @return mixed
     */
    public function getCustomerType(Order $order)
    {
        return $order->getCollectorbankCustomerType();
    }

    /**
     * Set customer type on order (whether business or private)
     *
     * @param Order $order
     * @param       $id
     * @return $this
     */
    public function setCustomerType(Order $order, $id)
    {
        $order->setCollectorbankCustomerType($id);

        return $this;
    }

    protected function getData(Order $order)
    {
        $data = json_decode($order->getCollectorbankData());

        return ($data) ? get_object_vars($data) : [];
    }


    protected function setData(Order $order, $data)
    {
        $order->setCollectorbankData(json_encode($data));

        return $this;
    }

    /**
     * Set org number on order
     *
     * @param Order $order
     * @param       $orgNumber
     * @return OrderHandler
     */
    public function setOrgNumber(Order $order, $orgNumber)
    {
        return $this->setAdditionalData($order, 'org_number', $orgNumber);
    }

    /**
     * Get org number from order
     *
     * @param Order $order
     * @return mixed|null
     */
    public function getOrgNumber(Order $order)
    {
        return $this->getAdditionalData($order, 'org_number');
    }

    /**
     * Set reference on order
     *
     * @param Order $order
     * @param       $reference
     * @return OrderHandler
     */
    public function setReference(Order $order, $reference)
    {
        return $this->setAdditionalData($order, 'reference', $reference);
    }

    /**
     * Get reference from order
     *
     * @param Order $order
     * @return mixed|null
     */
    public function getReference(Order $order)
    {
        return $this->getAdditionalData($order, 'reference');
    }

    /**
     * Set store id on order
     *
     * @param Order $order
     * @param       $reference
     * @return OrderHandler
     */
    public function setStoreId(Order $order, $reference)
    {
        return $this->setAdditionalData($order, 'store_id', $reference);
    }

    /**
     * Get store id on order
     *
     * @param Order $order
     * @return mixed|null
     */
    public function getStoreId(Order $order)
    {
        return $this->getAdditionalData($order, 'store_id');
    }

    /**
     * Get additional data on order, an array of extra collector data
     *
     * @param Order  $order
     * @param string $name
     * @return mixed|null
     */
    protected function getAdditionalData(Order $order, string $name)
    {
        $data = $this->getData($order);
        if (!isset($data[$name])) {

            return null;
        }

        return $data[$name];
    }

    /**
     * Get flag if customer has subscribed to newsletter or not
     *
     * @param Order $order
     * @return bool
     */
    public function getNewsletterSubscribe(Order $order):bool
    {
        $newsletterSubscribe = $this->getAdditionalData($order, 'newsletter_subscribe');

        return (1 == (int)$newsletterSubscribe) ? true : false;
    }


    /**
     * Set delivery checkout shipment data on quote
     *
     * @param Order $quote
     * @param       $shippingInfo
     * @return OrderHandler
     */
    public function setDeliveryCheckoutShipmentData(Order $order, $shippingInfo)
    {
        return $this->setAdditionalData($order, 'delivery_checkout_shipment_data', json_encode($shippingInfo));
    }

    /**
     * Get delivery checkout shipment data from quote
     *
     * @param Order $quote
     * @return array|null
     */
    public function getDeliveryCheckoutShipmentData(Order $order)
    {
        $shippingData = $this->getAdditionalData($order, 'delivery_checkout_shipment_data');
        $shippingData = json_decode($shippingData);

        return ($shippingData) ? get_object_vars($shippingData) : [];
    }


    /**
     * Set delivery checkout data on quote
     *
     * @param Quote $quote
     * @param       $shippingInfo
     * @return OrderHandler
     */
    public function setDeliveryCheckoutData(Order $order, $shippingInfo)
    {
        return $this->setAdditionalData($order, 'delivery_checkout_data', json_encode($shippingInfo));
    }

    /**
     * Get delivery checkout data from quote
     *
     * @param Quote $quote
     * @return array|null
     */
    public function getDeliveryCheckoutData(Order $order)
    {
        $shippingData = $this->getAdditionalData($order, 'delivery_checkout_data');
        $shippingData = json_decode($shippingData);

        return ($shippingData) ? get_object_vars($shippingData) : [];
    }
    
    /**
     * @param Order  $order
     * @param string $name
     * @param string $value
     * @return OrderHandler
     */
    protected function setAdditionalData(Order $order, string $name, string $value)
    {
        $data = $this->getData($order);
        $data[$name] = $value;

        return $this->setData($order, $data);
    }
}
