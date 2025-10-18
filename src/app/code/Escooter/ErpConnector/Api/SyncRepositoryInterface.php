<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Api;

use Escooter\ErpConnector\Api\Data\SyncInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface SyncRepositoryInterface
{
    /**
     * Save sync record
     *
     * @param SyncInterface $sync
     * @return SyncInterface
     * @throws CouldNotSaveException
     */
    public function save(SyncInterface $sync): SyncInterface;

    /**
     * Get sync record by ID
     *
     * @param int $syncId
     * @return SyncInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $syncId): SyncInterface;

    /**
     * Get sync record by order ID
     *
     * @param int $orderId
     * @return SyncInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): SyncInterface;

    /**
     * Get sync record by order increment ID
     *
     * @param string $orderIncrementId
     * @return SyncInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderIncrementId(string $orderIncrementId): SyncInterface;

    /**
     * Get sync record by idempotency key
     *
     * @param string $idempotencyKey
     * @return SyncInterface
     * @throws NoSuchEntityException
     */
    public function getByIdempotencyKey(string $idempotencyKey): SyncInterface;

    /**
     * Get list of sync records
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete sync record
     *
     * @param SyncInterface $sync
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(SyncInterface $sync): bool;

    /**
     * Delete sync record by ID
     *
     * @param int $syncId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $syncId): bool;
}
