<?php

namespace Webbhuset\CollectorCheckout\Setup;

/**
 * Class InstallSchema
 *
 * @package Webbhuset\CollectorCheckout\Setup
 */
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * Install schema
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->addCollectorBankQuoteColumns($setup);
        $this->addCollectorBankSalesOrderColumns($setup);

        $setup->endSetup();
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     */
    protected function addCollectorBankQuoteColumns(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $table = $setup->getTable('quote');

        $columns = [
            'collectorbank_private_id' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Walley private id',
            ],
            'collectorbank_public_id' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Walley public id',
            ],
            'collectorbank_customer_type' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Walley customer type',
            ],
            'collectorbank_data' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '64k',
                'nullable' => true,
                'comment' => 'Walley data'
            ]
        ];
        $this->addColumns($columns, $table, $setup);
    }

    protected function addCollectorBankSalesOrderColumns(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $table = $setup->getTable('sales_order');

        $columns = [
            'collectorbank_private_id' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Walley private id',
            ],
            'collectorbank_public_id' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Walley public id',
            ],
            'collectorbank_customer_type' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Walley customer type',
            ],
            'collectorbank_data' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '64k',
                'nullable' => true,
                'comment' => 'Walley data',
            ]
        ];
        $this->addColumns($columns, $table, $setup);
    }

    protected function addColumns($columns, $table, \Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        foreach ($columns as $name => $definition) {
            $connection->addColumn($table, $name, $definition);
        }
    }
}
