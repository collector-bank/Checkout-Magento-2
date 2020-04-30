<?php

namespace Webbhuset\CollectorCheckout\Config\Source\Customer;

/**
 * Class Type
 *
 * @package Webbhuset\CollectorCheckout\Config\Source\Customer
 */
class Type implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * $var private customer type id available int
     */
    const PRIVATE_CUSTOMERS = 1;
    /**
     * $var business customer type id available int
     */
    const BUSINESS_CUSTOMERS = 2;
    /**
     * $var both customer type id available int
     */
    const BOTH_CUSTOMERS = 3;

    /**
     * Returns an array of possible settings for the customer switcher block
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            self::PRIVATE_CUSTOMERS => __('Private customers'),
            self::BUSINESS_CUSTOMERS => __('Business customers'),
            self::BOTH_CUSTOMERS => __('Both')
        ];
    }
}
