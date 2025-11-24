<?php

namespace Webbhuset\CollectorCheckout\Helper;

use Magento\Framework\App\ResourceConnection;

class ProductType
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Product constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get product type by product ID
     *
     * @param int $productId
     * @return string|null
     */
    public function getProductTypeById(int $productId): ?string
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('catalog_product_entity');

        $select = $connection->select()
            ->from($tableName, ['type_id'])
            ->where('entity_id = ?', $productId)
            ->limit(1);

        return $connection->fetchOne($select) ?: null;
    }
}

