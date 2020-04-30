<?php

namespace Webbhuset\CollectorCheckout\Config\Source\Customer;

/**
 * Class DefaultType
 *
 * @package Webbhuset\CollectorCheckout\Config\Source\Customer
 */
class DefaultType implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * $var private customer type id
     */
    const PRIVATE_CUSTOMERS = 1;
    /**
     * $var business customer type id
     */
    const BUSINESS_CUSTOMERS = 2;

    /**
     * Returns an array with available customer types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            self::PRIVATE_CUSTOMERS => __('Private customers'),
            self::BUSINESS_CUSTOMERS => __('Business customers'),
        ];
    }
}
