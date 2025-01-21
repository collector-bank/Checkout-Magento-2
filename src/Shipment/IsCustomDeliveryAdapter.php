<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Shipment;

use Webbhuset\CollectorCheckout\Data\ExtractShippingOptionFee;

class IsCustomDeliveryAdapter
{
    private ExtractShippingOptionFee $extractShippingOptionFee;

    public function __construct(
        ExtractShippingOptionFee $extractShippingOptionFee
    ) {
        $this->extractShippingOptionFee = $extractShippingOptionFee;
    }

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
        $shippingChoice = $shipment["shipments"][0]['shippingChoice'];

        return (float) $shippingChoice['fee'] + $this->extractShippingOptionFee->execute($shippingChoice);
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
