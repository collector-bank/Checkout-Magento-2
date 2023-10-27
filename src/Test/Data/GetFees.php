<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Test\Data;

use Webbhuset\CollectorCheckoutSDK\Checkout\Fees\Fee;
use Webbhuset\CollectorCheckoutSDK\Checkout\Fees;

class GetFees
{
    public function execute(): Fees
    {
        $shippingFee = new Fee(
            (string) 1,
            "Shipping fee",
            20,
            25
        );

        $directInvoiceFee = new Fee(
            (string) 2,
            "Direct invoice fee",
            100,
            25
        );

        return new Fees($shippingFee, $directInvoiceFee);
    }
}
