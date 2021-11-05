<?php

namespace Webbhuset\CollectorCheckout\Config\Source\Checkout;

/**
 * Class Country
 *
 * @package Webbhuset\CollectorCheckout\Config\Source\Country
 */
class Version
    implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var string Swedish country code
     */
    const V1  = "v1";
    const V2  = "v2";

    /**
     * Returns an array with country name per country code
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            self::V1 => __('Version 1'),
            self::V2 => __('Version 2'),
        ];
    }
}
