<?php

namespace Webbhuset\CollectorCheckout\Api;

interface CarrierDataRepositoryInterface
{
    /**
     * Get carrier data from order id
     *
     * @param int $orderId
     *
     * @return \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface
     */
    public function get(int $orderId);

    /**
     * Save carrier data on order
     *
     * @param \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface $carrierData
     * @param int $orderId
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function save(\Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface $carrierData, int $orderId);
}