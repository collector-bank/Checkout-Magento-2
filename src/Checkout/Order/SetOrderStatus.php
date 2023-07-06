<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Checkout\Order;

use Magento\Framework\App\ResourceConnection;

class SetOrderStatus
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function execute($orderId, $status, $state)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('sales_order');

        $data = ['state' => $state, 'status' => $status];
        $connection->update($tableName, $data, 'entity_id = ' . (int)$orderId);
    }
}
