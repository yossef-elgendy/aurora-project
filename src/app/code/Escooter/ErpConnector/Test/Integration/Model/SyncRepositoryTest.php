<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Test\Integration\Model;

use Escooter\ErpConnector\Api\Data\SyncInterface;
use Escooter\ErpConnector\Api\SyncRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SyncRepositoryTest extends TestCase
{
    /**
     * @var SyncRepositoryInterface
     */
    private $syncRepository;

    /**
     * @var \Escooter\ErpConnector\Model\SyncFactory
     */
    private $syncFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->syncRepository = $objectManager->get(SyncRepositoryInterface::class);
        $this->syncFactory = $objectManager->get(\Escooter\ErpConnector\Model\SyncFactory::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testSaveAndGetById()
    {
        // Create sync record
        $sync = $this->syncFactory->create();
        $sync->setOrderId(123);
        $sync->setOrderIncrementId('000000123');
        $sync->setStatus(SyncInterface::STATUS_PENDING);
        $sync->setAttempts(0);
        $sync->setMaxAttempts(5);
        $sync->setIdempotencyKey('test-key-123');

        // Save
        $savedSync = $this->syncRepository->save($sync);
        $this->assertNotNull($savedSync->getSyncId());

        // Get by ID
        $loadedSync = $this->syncRepository->getById($savedSync->getSyncId());
        $this->assertEquals($sync->getOrderId(), $loadedSync->getOrderId());
        $this->assertEquals($sync->getOrderIncrementId(), $loadedSync->getOrderIncrementId());
        $this->assertEquals($sync->getStatus(), $loadedSync->getStatus());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testGetByOrderIncrementId()
    {
        // Create sync record
        $sync = $this->syncFactory->create();
        $sync->setOrderId(456);
        $sync->setOrderIncrementId('000000456');
        $sync->setStatus(SyncInterface::STATUS_SUCCESS);
        $sync->setIdempotencyKey('test-key-456');
        $this->syncRepository->save($sync);

        // Get by order increment ID
        $loadedSync = $this->syncRepository->getByOrderIncrementId('000000456');
        $this->assertEquals(456, $loadedSync->getOrderId());
        $this->assertEquals('000000456', $loadedSync->getOrderIncrementId());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testGetByIdempotencyKey()
    {
        // Create sync record
        $idempotencyKey = 'unique-key-' . uniqid();
        $sync = $this->syncFactory->create();
        $sync->setOrderId(789);
        $sync->setOrderIncrementId('000000789');
        $sync->setStatus(SyncInterface::STATUS_QUEUED);
        $sync->setIdempotencyKey($idempotencyKey);
        $this->syncRepository->save($sync);

        // Get by idempotency key
        $loadedSync = $this->syncRepository->getByIdempotencyKey($idempotencyKey);
        $this->assertEquals(789, $loadedSync->getOrderId());
        $this->assertEquals($idempotencyKey, $loadedSync->getIdempotencyKey());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testGetList()
    {
        // Create multiple sync records
        for ($i = 1; $i <= 3; $i++) {
            $sync = $this->syncFactory->create();
            $sync->setOrderId(100 + $i);
            $sync->setOrderIncrementId('00000010' . $i);
            $sync->setStatus(SyncInterface::STATUS_PENDING);
            $sync->setIdempotencyKey('test-key-10' . $i);
            $this->syncRepository->save($sync);
        }

        // Get list with search criteria
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', SyncInterface::STATUS_PENDING, 'eq')
            ->create();

        $searchResults = $this->syncRepository->getList($searchCriteria);
        $this->assertGreaterThanOrEqual(3, $searchResults->getTotalCount());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testUpdate()
    {
        // Create sync record
        $sync = $this->syncFactory->create();
        $sync->setOrderId(111);
        $sync->setOrderIncrementId('000000111');
        $sync->setStatus(SyncInterface::STATUS_PENDING);
        $sync->setIdempotencyKey('test-key-111');
        $savedSync = $this->syncRepository->save($sync);

        // Update
        $savedSync->setStatus(SyncInterface::STATUS_SUCCESS);
        $savedSync->setErpReference('ERP-111');
        $this->syncRepository->save($savedSync);

        // Verify update
        $loadedSync = $this->syncRepository->getById($savedSync->getSyncId());
        $this->assertEquals(SyncInterface::STATUS_SUCCESS, $loadedSync->getStatus());
        $this->assertEquals('ERP-111', $loadedSync->getErpReference());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testDelete()
    {
        // Create sync record
        $sync = $this->syncFactory->create();
        $sync->setOrderId(222);
        $sync->setOrderIncrementId('000000222');
        $sync->setStatus(SyncInterface::STATUS_FAILED);
        $sync->setIdempotencyKey('test-key-222');
        $savedSync = $this->syncRepository->save($sync);
        $syncId = $savedSync->getSyncId();

        // Delete
        $this->syncRepository->delete($savedSync);

        // Verify deletion
        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->syncRepository->getById($syncId);
    }
}

