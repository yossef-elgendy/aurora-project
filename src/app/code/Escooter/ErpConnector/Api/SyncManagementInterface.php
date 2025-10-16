<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Api;

interface SyncManagementInterface
{
    /**
     * Sync order by increment ID
     *
     * @param string $orderIncrementId
     * @return array
     * @throws \Exception
     */
    public function syncOrder(string $orderIncrementId): array;

    /**
     * Resync order by increment ID
     *
     * @param string $orderIncrementId
     * @return array
     * @throws \Exception
     */
    public function resyncOrder(string $orderIncrementId): array;

    /**
     * Get sync status for order
     *
     * @param string $orderIncrementId
     * @return array
     * @throws \Exception
     */
    public function getSyncStatus(string $orderIncrementId): array;

    /**
     * Process webhook from ERP
     *
     * @param string $orderIncrementId
     * @param string $erpReference
     * @param string $status
     * @param string|null $signature
     * @return array
     * @throws \Exception
     */
    public function processWebhook(
        string $orderIncrementId,
        string $erpReference,
        string $status,
        ?string $signature = null
    ): array;

}

