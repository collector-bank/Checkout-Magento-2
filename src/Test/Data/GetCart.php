<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Test\Data;

use Webbhuset\CollectorCheckoutSDK\Checkout\Cart;
use Webbhuset\CollectorCheckoutSDK\Checkout\Cart\Item;

class GetCart
{
    public function execute():Cart
    {
        $item = $this->getItem();

        return $this->getCart($item);
    }

    private function getItem(): Item
    {
        return new Item(
            'my-sku',
            'Kanelbulle',
            59,
            1,
            25,
            false,
            'my-sku'
        );
    }


    private function getCart(Item $item):Cart
    {
        return new Cart([$item]);
    }
}
