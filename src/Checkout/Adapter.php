<?php

namespace Webbhuset\CollectorCheckout\Checkout;

use Magento\Payment\Model\InfoInterface;

/**
 * Class Adapter
 *
 * @package Webbhuset\CollectorCheckout\Checkout
 */
class Adapter extends \Magento\Payment\Model\Method\Adapter
{
    public function canVoid()
    {
        $additionalInformation = $this->getInfoInstance()->getAdditionalInformation();

        if (isset($additionalInformation['payment_name'])
            && \Webbhuset\CollectorCheckout\Gateway\Config::PAYMENT_METHOD_SWISH == $additionalInformation['payment_name']) {

            return false;
        }

        return parent::canVoid();
    }
}
