<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Config\Source;

use Magento\Shipping\Model\Config;

class IconsSource implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var Config
     */
    private $shippingConfig;

    public function __construct(
        Config $shippingConfig
    ) {
        $this->shippingConfig = $shippingConfig;
    }

    public function toOptionArray()
    {
        $methods = $this->shippingConfig->getAllCarriers();

        $result = [];
        foreach ($methods as $method) {
            $result[] = [
                'value' => $method->getCarrierCode(),
                'label' => $method->getCarrierCode(),
            ];
        }
        return $result;
    }


}
