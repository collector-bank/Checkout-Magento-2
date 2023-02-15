<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Shipment;

use Magento\Quote\Api\Data\ShippingMethodInterface;

class ConvertToShipment
{
    /**
     * @var GetIconForShippingMethod
     */
    private $getIconForShippingMethod;

    public function __construct(
        GetIconForShippingMethod $getIconForShippingMethod
    ) {
        $this->getIconForShippingMethod = $getIconForShippingMethod;
    }

    /**
     * Converts a list of Magento shipment methods to Walley delivery adapter shipment format
     *
     * @param ShippingMethodInterface[] $shippingMethods
     * @return array
     */
    public function execute(array $shippingMethods):array
    {
        $shippingChoices = [];
        foreach ($shippingMethods as $shippingMethod) {
            $shippingChoices[] = $this->shippingMethodToShipmentChoice($shippingMethod);
        }

        return [
            'shipments' => [
                [
                    'id' => 'magento-delivery-methods',
                    'name' => (string)__('Select delivery method'),
                    "metadata" => [
                        "key-for-shipment" => "metadata-for-shipment",
                        "from-store-id" => "2057"
                    ],
                    'shippingChoices' => $shippingChoices
                ]
            ]
        ];
    }

    public function shippingMethodToShipmentChoice(ShippingMethodInterface $shippingMethod):array
    {
        return [
            'id' => $shippingMethod->getMethodCode(),
            'name' => $shippingMethod->getMethodTitle(),
            'description' => $shippingMethod->getCarrierTitle(),
            'fee' => (float)$shippingMethod->getAmount(),
            'icon' => $this->getIconForShippingMethod->execute($shippingMethod->getMethodCode()),
            'destinations' => null,
        ];
    }
}
