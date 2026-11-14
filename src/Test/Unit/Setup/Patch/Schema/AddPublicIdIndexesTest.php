<?php

declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Test\Unit\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Webbhuset\CollectorCheckout\Setup\Patch\Schema\AddPublicIdIndexes;

class AddPublicIdIndexesTest extends TestCase
{
    /**
     * @var SchemaSetupInterface|Stub
     */
    private $setup;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->setup = $this->createStub(SchemaSetupInterface::class);
        $this->setup->method('getConnection')->willReturn($this->connection);
        $this->setup->method('getTable')->willReturnCallback(
            function ($tableName) {
                return 'prefix_' . $tableName;
            }
        );
        $this->setup->method('getIdxName')->willReturnCallback(
            function ($tableName, $fields) {
                return strtoupper($tableName . '_' . implode('_', $fields));
            }
        );
    }

    public function testAddsIndexOnQuoteAndSalesOrder(): void
    {
        $this->connection->method('tableColumnExists')->willReturn(true);
        $this->connection->method('getIndexList')->willReturn([
            'PRIMARY' => ['COLUMNS_LIST' => ['entity_id']],
        ]);

        $added = [];
        $this->connection->expects($this->exactly(2))
            ->method('addIndex')
            ->willReturnCallback(
                function ($table, $indexName, $fields) use (&$added) {
                    $added[] = [$table, $indexName, $fields];
                    return null;
                }
            );

        $this->createPatch()->apply();

        $this->assertSame(
            [
                ['prefix_quote', 'QUOTE_COLLECTORBANK_PUBLIC_ID', ['collectorbank_public_id']],
                ['prefix_sales_order', 'SALES_ORDER_COLLECTORBANK_PUBLIC_ID', ['collectorbank_public_id']],
            ],
            $added
        );
    }

    public function testSkipsTableWithSingleColumnIndexOnPublicId(): void
    {
        $this->assertSkipsSalesOrderIndexedBy(['collectorbank_public_id']);
    }

    public function testSkipsTableWithCompositeIndexLedByPublicId(): void
    {
        $this->assertSkipsSalesOrderIndexedBy(['collectorbank_public_id', 'store_id']);
    }

    public function testAddsIndexWhenColumnIsOnlyASecondaryIndexColumn(): void
    {
        $this->connection->method('tableColumnExists')->willReturn(true);
        $this->connection->method('getIndexList')->willReturn([
            'IDX_COMPOSITE' => ['COLUMNS_LIST' => ['store_id', 'collectorbank_public_id']],
        ]);

        $this->connection->expects($this->exactly(2))->method('addIndex');

        $this->createPatch()->apply();
    }

    public function testSkipsTableWithoutColumn(): void
    {
        $this->connection->method('tableColumnExists')->willReturnCallback(
            function ($table, $column) {
                return $table === 'prefix_sales_order' && $column === 'collectorbank_public_id';
            }
        );
        $this->connection->method('getIndexList')->willReturn([]);

        $this->connection->expects($this->once())
            ->method('addIndex')
            ->with('prefix_sales_order', 'SALES_ORDER_COLLECTORBANK_PUBLIC_ID', ['collectorbank_public_id']);

        $this->createPatch()->apply();
    }

    private function assertSkipsSalesOrderIndexedBy(array $columns): void
    {
        $this->connection->method('tableColumnExists')->willReturn(true);
        $this->connection->method('getIndexList')->willReturnCallback(
            function ($table) use ($columns) {
                if ($table === 'prefix_sales_order') {
                    return ['IDX_MANUAL' => ['COLUMNS_LIST' => $columns]];
                }
                return [];
            }
        );

        $this->connection->expects($this->once())
            ->method('addIndex')
            ->with('prefix_quote', 'QUOTE_COLLECTORBANK_PUBLIC_ID', ['collectorbank_public_id']);

        $this->createPatch()->apply();
    }

    private function createPatch(): AddPublicIdIndexes
    {
        return new AddPublicIdIndexes($this->setup);
    }
}
