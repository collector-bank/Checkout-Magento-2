<?php

namespace Webbhuset\CollectorCheckout\Config\Source\Order;

/**
 * Order Statuses source model
 */
class Holded extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        \Magento\Sales\Model\Order::STATE_HOLDED,
    ];
}
