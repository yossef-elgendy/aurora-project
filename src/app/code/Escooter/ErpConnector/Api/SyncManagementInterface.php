<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Api;

use Escooter\ErpConnector\Api\Data\SyncOrderResponseInterface;
use Escooter\ErpConnector\Api\Data\SyncStatusResponseInterface;
use Escooter\ErpConnector\Api\Data\WebhookResponseInterface;

interface SyncManagementInterface
{
    /**
     * Sync order by increment ID
     *
     * @param string $orderIncrementId
     * @return SyncOrderResponseInterface
     * @throws \Exception
     */
    public function syncOrder(string $orderIncrementId): SyncOrderResponseInterface;

    /**
     * Resync order by increment ID
     *
     * @param string $orderIncrementId
     * @return SyncOrderResponseInterface
     * @throws \Exception
     */
    public function resyncOrder(string $orderIncrementId): SyncOrderResponseInterface;

    /**
     * Get sync status for order
     *
     * @param string $orderIncrementId
     * @return SyncStatusResponseInterface
     * @throws \Exception
     */
    public function getSyncStatus(string $orderIncrementId): SyncStatusResponseInterface;

    /**
     * Process webhook from ERP
     *
     * @param string $orderIncrementId
     * @param string $erpReference
     * @param string $status
     * @param string|null $signature
     * @return WebhookResponseInterface
     * @throws \Exception
     */
    public function processWebhook(
        string $orderIncrementId,
        string $erpReference,
        string $status,
        ?string $signature = null
    ): WebhookResponseInterface;

}

