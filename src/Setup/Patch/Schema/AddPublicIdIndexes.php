<?php

declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Setup\Patch\Schema;

class AddPublicIdIndexes implements \Magento\Framework\Setup\Patch\SchemaPatchInterface
{
    const COLUMN = 'collectorbank_public_id';

    private $setup;

    public function __construct(
        \Magento\Framework\Setup\SchemaSetupInterface $setup
    ) {
        $this->setup = $setup;
    }

    public function apply()
    {
        $this->setup->startSetup();
        $connection = $this->setup->getConnection();

        foreach (['quote', 'sales_order'] as $tableName) {
            $table = $this->setup->getTable($tableName);

            if (!$connection->tableColumnExists($table, self::COLUMN)) {
                continue;
            }

            if ($this->hasIndexLedByColumn($connection->getIndexList($table))) {
                continue;
            }

            $connection->addIndex(
                $table,
                $this->setup->getIdxName($tableName, [self::COLUMN]),
                [self::COLUMN]
            );
        }

        $this->setup->endSetup();

        return $this;
    }

    private function hasIndexLedByColumn(array $indexes): bool
    {
        foreach ($indexes as $index) {
            $columns = $index['COLUMNS_LIST'] ?? [];
            if (reset($columns) === self::COLUMN) {
                return true;
            }
        }

        return false;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
