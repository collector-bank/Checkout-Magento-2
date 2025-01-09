<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Shipment;

class IsCustomDeliveryAdapter
{
    public function execute(
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ):bool {
        $shipment = $checkoutData->getShipping();
        if (!$shipment) {
            return false;
        }
        $shipmentData = $shipment->getData();
        if (
            isset($shipmentData["shipments"][0]["id"])
            && isset($shipmentData["shipments"][0]['shippingChoice']['id'])
        ) {
            return true;
        }
        return false;
    }

    public function getDeliveryFee(\Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData):?float
    {
        if (!$this->execute($checkoutData)) {
            return null;
        }
        $shipment = $checkoutData->getShipping()->getData();

        return (float) $shipment["shipments"][0]['shippingChoice']['fee'];
    }

    public function getDeliveryMethod(\Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData):?string
    {
        if (!$this->execute($checkoutData)) {
            return null;
        }
        $shipment = $checkoutData->getShipping()->getData();

        return (string) $shipment["shipments"][0]['shippingChoice']['id'];
    }
}
