<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Shipment;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Shipping\Helper\Carrier;

class ConvertToShipment
{
    /**
     * @var GetIconForShippingMethod
     */
    private $getIconForShippingMethod;
    /**
     * @var Carrier
     */
    private $carrier;
    private GetBadgesForShippingMethod $getBadgesForShippingMethod;

    public function __construct(
        GetIconForShippingMethod $getIconForShippingMethod,
        Carrier $carrier,
        GetBadgesForShippingMethod $getBadgesForShippingMethod
    ) {
        $this->getIconForShippingMethod = $getIconForShippingMethod;
        $this->carrier = $carrier;
        $this->getBadgesForShippingMethod = $getBadgesForShippingMethod;
    }

    /**
     * Converts a list of Magento shipment methods to Walley delivery adapter shipment format
     *
     * @param ShippingMethodInterface[] $shippingMethods
     * @return array
     */
    public function execute(array $shippingMethods, CartInterface $quote):array
    {
        $shippingChoices = [];
        foreach ($shippingMethods as $shippingMethod) {
            $shippingChoices[] = $this->shippingMethodToShipmentChoice($shippingMethod, $quote);
        }
        $sortOrder = array_column($shippingChoices, 'sort_order');
        array_multisort($sortOrder, SORT_ASC, $shippingChoices);

        return [
            'shipments' => [
                [
                    'id' => 'magento-delivery-methods',
                    'name' => (string)__('Select delivery method'),
                    "metadata" => [
                        "key-for-shipment" => "metadata-for-shipment",
                    ],
                    'shippingChoices' => $shippingChoices
                ]
            ]
        ];
    }

    public function shippingMethodToShipmentChoice(ShippingMethodInterface $shippingMethod, CartInterface $quote):array
    {
        return [
            'id' => $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode(),
            'name' => $shippingMethod->getMethodTitle(),
            'description' => $shippingMethod->getCarrierTitle(),
            'fee' => (float)$shippingMethod->getPriceInclTax(),
            'icon' => $this->getIconForShippingMethod->execute($shippingMethod->getCarrierCode()),
            'badges' => $this->getBadgesForShippingMethod->execute($shippingMethod->getCarrierCode(), (int) $quote->getStoreId()),
            'destinations' => null,
            'sort_order' => (int) $this->carrier->getCarrierConfigValue($shippingMethod->getCarrierCode(), 'sort_order'),
        ];
    }

}
