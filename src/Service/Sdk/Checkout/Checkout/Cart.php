<?php
declare(strict_types=1);


namespace Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Checkout;

use Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Checkout\Cart\Item;

class Cart
{
    private array $items = [];
    private $totalAmount;

    public function __construct(
        array $items
    ) {
        // Type check items
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    private function addItem(Item $item)
    {
        $this->items[] = $item;
    }

    public function getItems() : array
    {
        return $this->items;
    }

    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    public function toArray() : array
    {
        $items = array_map(function($item) {
            return $item->toArray();
        }, $this->getItems());

        return [
            'items' => $items,
        ];
    }
}
