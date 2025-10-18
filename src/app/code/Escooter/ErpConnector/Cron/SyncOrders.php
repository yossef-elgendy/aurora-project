<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Cron;

use Escooter\ErpConnector\Api\Data\SyncInterface;
use Escooter\ErpConnector\Helper\Config;
use Escooter\ErpConnector\Model\ErpSyncService;
use Escooter\ErpConnector\Model\ResourceModel\Sync\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

class SyncOrders
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CollectionFactory
     */
    private $syncCollectionFactory;

    /**
     * @var ErpSyncService
     */
    private $erpSyncService;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Config $config
     * @param CollectionFactory $syncCollectionFactory
     * @param ErpSyncService $erpSyncService
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        CollectionFactory $syncCollectionFactory,
        ErpSyncService $erpSyncService,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->syncCollectionFactory = $syncCollectionFactory;
        $this->erpSyncService = $erpSyncService;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute()
    {
        // Check if module and cron are enabled
        if (!$this->config->isEnabled() || !$this->config->isCronEnabled()) {
            return;
        }

        $this->logger->info('ERP Sync Cron: Starting');

        try {
            $collection = $this->syncCollectionFactory->create();
            
            // Get records that are ready for sync
            $collection->addFieldToFilter('status', [
                'in' => [
                    SyncInterface::STATUS_PENDING,
                    SyncInterface::STATUS_QUEUED,
                    SyncInterface::STATUS_FAILED
                ]
            ]);

            // Filter by next_attempt_at
            $currentTime = $this->dateTime->gmtDate();
            $collection->addFieldToFilter(
                ['next_attempt_at', 'next_attempt_at'],
                [
                    ['lteq' => $currentTime],
                    ['null' => true]
                ]
            );

            // Order by created_at to process oldest first
            $collection->setOrder('created_at', 'ASC');

            // Limit to prevent timeout
            $collection->setPageSize(100);

            $count = $collection->getSize();
            $this->logger->info("ERP Sync Cron: Found {$count} records to process");

            $successCount = 0;
            $failureCount = 0;

            foreach ($collection as $sync) {
                try {
                    $this->logger->debug('Processing sync record', [
                        'sync_id' => $sync->getSyncId(),
                        'order_increment_id' => $sync->getOrderIncrementId(),
                        'attempts' => $sync->getAttempts()
                    ]);

                    $result = $this->erpSyncService->processSync($sync);
                    
                    if ($result) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }

                } catch (\Exception $e) {
                    $failureCount++;
                    $this->logger->error('ERP Sync Cron: Error processing sync record', [
                        'sync_id' => $sync->getSyncId(),
                        'order_increment_id' => $sync->getOrderIncrementId(),
                        'error' => $e->getMessage(),
                        'exception' => $e
                    ]);
                }
            }

            $this->logger->info('ERP Sync Cron: Completed', [
                'total_processed' => $count,
                'success' => $successCount,
                'failed' => $failureCount
            ]);

        } catch (\Exception $e) {
            $this->logger->error('ERP Sync Cron: Fatal error', [
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
        }
    }
}
