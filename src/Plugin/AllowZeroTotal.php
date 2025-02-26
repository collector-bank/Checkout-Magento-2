<?php

namespace Webbhuset\CollectorCheckout\Plugin;

use Magento\Payment\Model\Checks\ZeroTotal as Subject;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

class AllowZeroTotal
{
    public function aroundIsApplicable(
        Subject $subject,
        \Closure $proceed,
        MethodInterface $paymentMethod,
        Quote $quote
    ) {
        if ($quote->getBaseGrandTotal() < 0.0001
            && $paymentMethod->getCode() === 'collectorbank_checkout'
        ) {
            return true;
        }

        return $proceed($paymentMethod, $quote);
    }
}
