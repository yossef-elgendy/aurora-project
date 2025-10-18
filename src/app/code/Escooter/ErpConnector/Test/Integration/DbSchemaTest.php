<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Test\Integration;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class DbSchemaTest extends TestCase
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    protected function setUp(): void
    {
        $this->resourceConnection = Bootstrap::getObjectManager()->get(ResourceConnection::class);
    }

    public function testVendorErpSyncTableExists()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('vendor_erp_sync');
        
        $this->assertTrue(
            $connection->isTableExists($tableName),
            'Table vendor_erp_sync should exist'
        );
    }

    public function testVendorErpSyncTableHasRequiredColumns()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('vendor_erp_sync');
        
        $requiredColumns = [
            'sync_id',
            'order_id',
            'order_increment_id',
            'status',
            'attempts',
            'max_attempts',
            'last_attempt_at',
            'next_attempt_at',
            'last_error',
            'erp_reference',
            'idempotency_key',
            'payload',
            'response',
            'created_at',
            'updated_at'
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                $connection->tableColumnExists($tableName, $column),
                "Column {$column} should exist in vendor_erp_sync table"
            );
        }
    }

    public function testVendorErpSyncTableHasIndexes()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('vendor_erp_sync');
        
        $indexes = $connection->getIndexList($tableName);
        
        $this->assertArrayHasKey('PRIMARY', $indexes, 'Primary key should exist');
        
        // Check for composite index on status and next_attempt_at
        $hasStatusIndex = false;
        foreach ($indexes as $index) {
            if (isset($index['COLUMNS_LIST']) &&
                in_array('status', $index['COLUMNS_LIST']) &&
                in_array('next_attempt_at', $index['COLUMNS_LIST'])) {
                $hasStatusIndex = true;
                break;
            }
        }
        $this->assertTrue($hasStatusIndex, 'Index on status and next_attempt_at should exist');
    }

    public function testSalesOrderTableHasErpSyncedColumn()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('sales_order');
        
        $this->assertTrue(
            $connection->tableColumnExists($tableName, 'erp_synced'),
            'Column erp_synced should exist in sales_order table'
        );
    }
}
