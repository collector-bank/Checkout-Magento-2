<?php


namespace Webbhuset\CollectorCheckout\Carrier;


/**
 * Class CarrierData
 *
 * @package Webbhuset\CollectorCheckout\Carrier
 */
class CarrierData implements \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @inheritDoc
     */
    public function setData($data = []) {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function getData():array
    {
        return $this->data;
    }

}