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
    private \Webbhuset\CollectorCheckout\Config\Config $config;

    /**
     * RemovePendingOrders constructor.
     *
     * @param \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Webbhuset\CollectorCheckout\Config\Config $config
    ) {
        $this->orderManager = $orderManager;
        $this->config = $config;
    }

    /**
     *
     */
    public function execute()
    {
        $orderManager = $this->orderManager->create();
        $orders = $orderManager->getPendingCollectorBankOrders();

        foreach ($orders as $order) {
            if ($this->config->getDeletePendingOrders()) {
                $orderManager->removeAndCancelOrder($order);
            } else {
                $orderManager->cancelOrderAndLog($order);
            }
        }
    }
}
