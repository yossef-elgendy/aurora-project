<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Test\Integration\Api;

use Escooter\ErpConnector\Api\Data\SyncStatusResponseInterface;
use Escooter\ErpConnector\Api\SyncManagementInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SyncManagementTest extends TestCase
{

    /**
     * @var SyncManagementInterface
     */
    private $syncManagement;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        $this->syncManagement = $objectManager->get(SyncManagementInterface::class);
    }


    /**
     * Test sync status for non-existent order
     */
    public function testGetSyncStatusForNonExistentOrder()
    {
        $response = $this->syncManagement->getSyncStatus('999999999');

        // Debug: Let's see what we actually get
        $this->assertNotNull($response, 'Response should not be null');
        $this->assertInstanceOf(SyncStatusResponseInterface::class, $response);
        
        $this->assertEquals('not_synced', $response->getStatus());
        $this->assertEquals('999999999', $response->getOrderIncrementId());
    }

    /**
     * Test webhook processing
     */
    public function testProcessWebhook()
    {
        // This should fail because no sync record exists for this order
        $response = $this->syncManagement->processWebhook(
            '000000TEST',
            'ERP-TEST-123',
            'accepted',
            null
        );

        // Should return failure because no sync record exists
        $this->assertFalse($response->getSuccess());
        $this->assertNotEmpty($response->getMessage());
    }
}

