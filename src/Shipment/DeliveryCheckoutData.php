<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Shipment;

class DeliveryCheckoutData
{
    protected $data = null;

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
