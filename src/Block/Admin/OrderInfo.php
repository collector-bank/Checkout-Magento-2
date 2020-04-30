<?php

namespace Webbhuset\CollectorCheckout\Block\Admin;

/**
 * Class OrderInfo
 *
 * @package Webbhuset\CollectorCheckout\Block\Admin
 */
class OrderInfo extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Webbhuset_CollectorCheckout::info/checkout.phtml';

    /**
     * Returns the payment information saved in the payment object for the order
     *
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentInfo()
    {
        if (!$this->getInfo()) {
            return [];
        }

        return $this->getInfo()->getAdditionalInformation();
    }
}
