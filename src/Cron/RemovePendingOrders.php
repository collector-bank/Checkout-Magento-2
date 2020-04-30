<?php

namespace Webbhuset\CollectorCheckout\Cron;

/**
 * Class RemovePendingOrders
 *
 * @package Webbhuset\CollectorCheckout\Cron
 */
class RemovePendingOrders
{
    /**
     * @var \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory
     */
    protected $orderManager;

    /**
     * RemovePendingOrders constructor.
     *
     * @param \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager
    ) {
        $this->orderManager = $orderManager;
    }

    /**
     *
     */
    public function execute()
    {
        $orderManager = $this->orderManager->create();

        $orders = $orderManager->getPendingCollectorBankOrders();

        foreach ($orders as $order) {
            $orderManager->removeOrderIfExists($order);
        }
    }
}
