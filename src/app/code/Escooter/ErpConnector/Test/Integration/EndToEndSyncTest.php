<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Test\Integration;

use Escooter\ErpConnector\Api\Data\SyncInterface;
use Escooter\ErpConnector\Api\SyncManagementInterface;
use Escooter\ErpConnector\Api\SyncRepositoryInterface;
use Escooter\ErpConnector\Helper\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class EndToEndSyncTest extends TestCase
{
    /**
     * @var SyncRepositoryInterface
     */
    private $syncRepository;

    /**
     * @var SyncManagementInterface
     */
    private $syncManagement;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->syncRepository = $objectManager->get(SyncRepositoryInterface::class);
        $this->syncManagement = $objectManager->get(SyncManagementInterface::class);
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        $this->config = $objectManager->get(Config::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
    }

    /**
     * Test complete sync flow
     * 
     * @magentoConfigFixture current_store erpconnector/general/enabled 1
     * @magentoConfigFixture current_store erpconnector/erp_api/base_url http://localhost/rest/V1/erpconnector
     */
    public function testCompleteSyncFlow()
    {
        // Step 1: Verify module is enabled
        $this->assertTrue($this->config->isEnabled(), 'Module should be enabled');

        // Step 2: Create a mock sync record (simulating order sync)
        $syncFactory = Bootstrap::getObjectManager()->get(\Escooter\ErpConnector\Model\SyncFactory::class);
        $sync = $syncFactory->create();
        $sync->setOrderId(999);
        $sync->setOrderIncrementId('100000999');
        $sync->setStatus(SyncInterface::STATUS_PENDING);
        $sync->setAttempts(0);
        $sync->setMaxAttempts(5);
        $sync->setIdempotencyKey('test-flow-key-999');
        
        $savedSync = $this->syncRepository->save($sync);
        $this->assertNotNull($savedSync->getSyncId(), 'Sync should be saved with ID');

        // Step 3: Verify sync was created
        $loadedSync = $this->syncRepository->getByOrderIncrementId('100000999');
        $this->assertEquals(SyncInterface::STATUS_PENDING, $loadedSync->getStatus());

        // Step 4: Test status retrieval via API
        $status = $this->syncManagement->getSyncStatus('100000999');
        $this->assertEquals('100000999', $status['order_increment_id']);
        $this->assertEquals(SyncInterface::STATUS_PENDING, $status['status']);
        $this->assertEquals(0, $status['attempts']);

        // Step 5: Update sync status to success
        $loadedSync->setStatus(SyncInterface::STATUS_SUCCESS);
        $loadedSync->setErpReference('ERP-TEST-999');
        $this->syncRepository->save($loadedSync);

        // Step 6: Verify status updated
        $finalStatus = $this->syncManagement->getSyncStatus('100000999');
        $this->assertEquals(SyncInterface::STATUS_SUCCESS, $finalStatus['status']);
        $this->assertEquals('ERP-TEST-999', $finalStatus['erp_reference']);
    }

    /**
     * Test sync record creation and retrieval by different identifiers
     * 
     * @magentoDbIsolation enabled
     */
    public function testSyncRecordMultipleIdentifiers()
    {
        $syncFactory = Bootstrap::getObjectManager()->get(\Escooter\ErpConnector\Model\SyncFactory::class);
        
        // Create sync
        $idempotencyKey = 'unique-test-key-' . time();
        $sync = $syncFactory->create();
        $sync->setOrderId(888);
        $sync->setOrderIncrementId('100000888');
        $sync->setStatus(SyncInterface::STATUS_QUEUED);
        $sync->setIdempotencyKey($idempotencyKey);
        $savedSync = $this->syncRepository->save($sync);

        // Test retrieval by sync_id
        $byId = $this->syncRepository->getById($savedSync->getSyncId());
        $this->assertEquals(888, $byId->getOrderId());

        // Test retrieval by order_id
        $byOrderId = $this->syncRepository->getByOrderId(888);
        $this->assertEquals('100000888', $byOrderId->getOrderIncrementId());

        // Test retrieval by order_increment_id
        $byIncrementId = $this->syncRepository->getByOrderIncrementId('100000888');
        $this->assertEquals($idempotencyKey, $byIncrementId->getIdempotencyKey());

        // Test retrieval by idempotency_key
        $byIdempotencyKey = $this->syncRepository->getByIdempotencyKey($idempotencyKey);
        $this->assertEquals(SyncInterface::STATUS_QUEUED, $byIdempotencyKey->getStatus());
    }

    /**
     * Test sync list filtering
     * 
     * @magentoDbIsolation enabled
     */
    public function testSyncListFiltering()
    {
        $syncFactory = Bootstrap::getObjectManager()->get(\Escooter\ErpConnector\Model\SyncFactory::class);
        
        // Create multiple syncs with different statuses
        $statuses = [
            SyncInterface::STATUS_PENDING,
            SyncInterface::STATUS_SUCCESS,
            SyncInterface::STATUS_FAILED
        ];

        foreach ($statuses as $index => $status) {
            $sync = $syncFactory->create();
            $sync->setOrderId(700 + $index);
            $sync->setOrderIncrementId('10000070' . $index);
            $sync->setStatus($status);
            $sync->setIdempotencyKey('filter-test-' . $index);
            $this->syncRepository->save($sync);
        }

        // Filter by status
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', SyncInterface::STATUS_PENDING, 'eq')
            ->create();

        $results = $this->syncRepository->getList($searchCriteria);
        $this->assertGreaterThanOrEqual(1, $results->getTotalCount());

        // Verify all returned items have pending status
        foreach ($results->getItems() as $item) {
            $this->assertEquals(SyncInterface::STATUS_PENDING, $item->getStatus());
        }
    }

    /**
     * Test mock ERP endpoint flow
     */
    public function testMockErpEndpoint()
    {
        $items = [
            ['sku' => 'MOCK-SKU-1', 'qty' => 5],
            ['sku' => 'MOCK-SKU-2', 'qty' => 10]
        ];

        $result = $this->syncManagement->mockUpdateStock(
            $items,
            '100000MOCK',
            'mock-idempotency-key'
        );

        $this->assertTrue($result['ok']);
        $this->assertEquals('Stock updated successfully (mock)', $result['message']);
        $this->assertEquals('100000MOCK', $result['order_increment_id']);
        $this->assertEquals('mock-idempotency-key', $result['idempotency_key']);
        $this->assertArrayHasKey('erp_reference', $result);
        $this->assertStringStartsWith('ERP-', $result['erp_reference']);
        $this->assertEquals(2, count($result['items']));
        $this->assertEquals('updated', $result['items'][0]['status']);
    }

    /**
     * Test sync retry logic with attempts tracking
     * 
     * @magentoDbIsolation enabled
     */
    public function testSyncRetryTracking()
    {
        $syncFactory = Bootstrap::getObjectManager()->get(\Escooter\ErpConnector\Model\SyncFactory::class);
        
        // Create sync
        $sync = $syncFactory->create();
        $sync->setOrderId(555);
        $sync->setOrderIncrementId('100000555');
        $sync->setStatus(SyncInterface::STATUS_FAILED);
        $sync->setAttempts(0);
        $sync->setMaxAttempts(3);
        $sync->setIdempotencyKey('retry-test-key');
        $this->syncRepository->save($sync);

        // Simulate retry attempts
        for ($i = 1; $i <= 3; $i++) {
            $sync->setAttempts($i);
            $sync->setLastError('Simulated error attempt ' . $i);
            $this->syncRepository->save($sync);
        }

        // Verify final state
        $finalSync = $this->syncRepository->getByOrderIncrementId('100000555');
        $this->assertEquals(3, $finalSync->getAttempts());
        $this->assertEquals(3, $finalSync->getMaxAttempts());
        $this->assertStringContains('attempt 3', $finalSync->getLastError());
    }
}

