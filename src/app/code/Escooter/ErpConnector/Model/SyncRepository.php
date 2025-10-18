<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model;

use Escooter\ErpConnector\Api\Data\SyncInterface;
use Escooter\ErpConnector\Api\SyncRepositoryInterface;
use Escooter\ErpConnector\Model\ResourceModel\Sync as SyncResource;
use Escooter\ErpConnector\Model\ResourceModel\Sync\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class SyncRepository implements SyncRepositoryInterface
{
    /**
     * @var SyncResource
     */
    private $resource;

    /**
     * @var SyncFactory
     */
    private $syncFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @param SyncResource $resource
     * @param SyncFactory $syncFactory
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        SyncResource $resource,
        SyncFactory $syncFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->syncFactory = $syncFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function save(SyncInterface $sync): SyncInterface
    {
        try {
            $this->resource->save($sync);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the sync record: %1', $exception->getMessage())
            );
        }
        return $sync;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $syncId): SyncInterface
    {
        $sync = $this->syncFactory->create();
        $this->resource->load($sync, $syncId);
        if (!$sync->getSyncId()) {
            throw new NoSuchEntityException(__('Sync record with id "%1" does not exist.', $syncId));
        }
        return $sync;
    }

    /**
     * @inheritdoc
     */
    public function getByOrderId(int $orderId): SyncInterface
    {
        $sync = $this->syncFactory->create();
        $this->resource->load($sync, $orderId, 'order_id');
        if (!$sync->getSyncId()) {
            throw new NoSuchEntityException(__('Sync record for order id "%1" does not exist.', $orderId));
        }
        return $sync;
    }

    /**
     * @inheritdoc
     */
    public function getByOrderIncrementId(string $orderIncrementId): SyncInterface
    {
        $sync = $this->syncFactory->create();
        $this->resource->load($sync, $orderIncrementId, 'order_increment_id');
        if (!$sync->getSyncId()) {
            throw new NoSuchEntityException(
                __('Sync record for order increment id "%1" does not exist.', $orderIncrementId)
            );
        }
        return $sync;
    }

    /**
     * @inheritdoc
     */
    public function getByIdempotencyKey(string $idempotencyKey): SyncInterface
    {
        $sync = $this->syncFactory->create();
        $this->resource->load($sync, $idempotencyKey, 'idempotency_key');
        if (!$sync->getSyncId()) {
            throw new NoSuchEntityException(
                __('Sync record with idempotency key "%1" does not exist.', $idempotencyKey)
            );
        }
        return $sync;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(SyncInterface $sync): bool
    {
        try {
            $this->resource->delete($sync);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the sync record: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $syncId): bool
    {
        return $this->delete($this->getById($syncId));
    }
}
