<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Data;

class ExtractShippingOptionFee
{
    public function execute(array $shippingChoice):float
    {
        $feeTotal = 0.0;
        if (isset($shippingChoice['options']) && is_array($shippingChoice['options'])) {
            foreach ($shippingChoice['options'] as $option) {
                if (isset($option['value']) && $option['value'] === true) {
                    $feeTotal += (float) $option['fee'];
                }
            }
        }

        return $feeTotal;
    }
}
