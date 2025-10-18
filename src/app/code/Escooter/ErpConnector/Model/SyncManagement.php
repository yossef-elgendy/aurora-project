<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model;

use Escooter\ErpConnector\Api\SyncManagementInterface;
use Escooter\ErpConnector\Api\SyncRepositoryInterface;
use Escooter\ErpConnector\Api\Data\SyncOrderResponseInterface;
use Escooter\ErpConnector\Api\Data\SyncStatusResponseInterface;
use Escooter\ErpConnector\Api\Data\WebhookResponseInterface;
use Escooter\ErpConnector\Helper\Config;
use Escooter\ErpConnector\Model\Response\SyncOrderResponseFactory;
use Escooter\ErpConnector\Model\Response\SyncStatusResponseFactory;
use Escooter\ErpConnector\Model\Response\WebhookResponseFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class SyncManagement implements SyncManagementInterface
{
    /**
     * @var SyncRepositoryInterface
     */
    private $syncRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ErpSyncService
     */
    private $erpSyncService;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SyncOrderResponseFactory
     */
    private $syncOrderResponseFactory;

    /**
     * @var SyncStatusResponseFactory
     */
    private $syncStatusResponseFactory;

    /**
     * @var WebhookResponseFactory
     */
    private $webhookResponseFactory;

    /**
     * @param SyncRepositoryInterface $syncRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ErpSyncService $erpSyncService
     * @param Config $config
     * @param LoggerInterface $logger
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SyncOrderResponseFactory $syncOrderResponseFactory
     * @param SyncStatusResponseFactory $syncStatusResponseFactory
     * @param WebhookResponseFactory $webhookResponseFactory
     */
    public function __construct(
        SyncRepositoryInterface $syncRepository,
        OrderRepositoryInterface $orderRepository,
        ErpSyncService $erpSyncService,
        Config $config,
        LoggerInterface $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SyncOrderResponseFactory $syncOrderResponseFactory,
        SyncStatusResponseFactory $syncStatusResponseFactory,
        WebhookResponseFactory $webhookResponseFactory
    ) {
        $this->syncRepository = $syncRepository;
        $this->orderRepository = $orderRepository;
        $this->erpSyncService = $erpSyncService;
        $this->config = $config;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->syncOrderResponseFactory = $syncOrderResponseFactory;
        $this->syncStatusResponseFactory = $syncStatusResponseFactory;
        $this->webhookResponseFactory = $webhookResponseFactory;
    }

    /**
     * @inheritdoc
     */
    public function syncOrder(string $orderIncrementId): SyncOrderResponseInterface
    {
        if (!$this->config->isEnabled()) {
            throw new LocalizedException(__('ERP Connector is not enabled'));
        }

        try {
            // Find order by increment ID
            $searchCriteria = $this->createSearchCriteria($orderIncrementId);
            $orders = $this->orderRepository->getList($searchCriteria);

            if ($orders->getTotalCount() === 0) {
                throw new NoSuchEntityException(__('Order with increment ID "%1" not found', $orderIncrementId));
            }

            $order = $orders->getItems()[0];

            // Create or get sync record
            $sync = $this->erpSyncService->createForOrder($order);

            // Process sync
            $result = $this->erpSyncService->processSync($sync);

            return $this->syncOrderResponseFactory->create([
                'success' => $result,
                'sync_id' => $sync->getSyncId(),
                'order_increment_id' => $orderIncrementId,
                'status' => $sync->getStatus(),
                'message' => $result ? 'Order synced successfully' : 'Order sync failed',
                'erp_reference' => $sync->getErpReference(),
                'attempts' => $sync->getAttempts(),
                'last_error' => $sync->getLastError()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error syncing order: ' . $e->getMessage(), [
                'order_increment_id' => $orderIncrementId,
                'exception' => $e
            ]);

            return $this->syncOrderResponseFactory->create([
                'success' => false,
                'order_increment_id' => $orderIncrementId,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function resyncOrder(string $orderIncrementId): SyncOrderResponseInterface
    {
        if (!$this->config->isEnabled()) {
            throw new LocalizedException(__('ERP Connector is not enabled'));
        }

        try {
            // Get existing sync record
            $sync = $this->syncRepository->getByOrderIncrementId($orderIncrementId);

            // Reschedule for immediate retry
            $sync = $this->erpSyncService->reschedule($sync);

            // Process sync
            $result = $this->erpSyncService->processSync($sync);

            return $this->syncOrderResponseFactory->create([
                'success' => $result,
                'sync_id' => $sync->getSyncId(),
                'order_increment_id' => $orderIncrementId,
                'status' => $sync->getStatus(),
                'message' => $result ? 'Order resynced successfully' : 'Order resync failed',
                'erp_reference' => $sync->getErpReference(),
                'attempts' => $sync->getAttempts(),
                'last_error' => $sync->getLastError()
            ]);

        } catch (NoSuchEntityException $e) {
            // If no sync record exists, create one
            return $this->syncOrder($orderIncrementId);

        } catch (\Exception $e) {
            $this->logger->error('Error resyncing order: ' . $e->getMessage(), [
                'order_increment_id' => $orderIncrementId,
                'exception' => $e
            ]);

            return $this->syncOrderResponseFactory->create([
                'success' => false,
                'order_increment_id' => $orderIncrementId,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getSyncStatus(string $orderIncrementId): SyncStatusResponseInterface
    {
        try {
            $sync = $this->syncRepository->getByOrderIncrementId($orderIncrementId);

            return $this->syncStatusResponseFactory->create([
                'sync_id' => $sync->getSyncId(),
                'order_id' => $sync->getOrderId(),
                'order_increment_id' => $sync->getOrderIncrementId(),
                'status' => $sync->getStatus(),
                'attempts' => $sync->getAttempts(),
                'max_attempts' => $sync->getMaxAttempts(),
                'last_attempt_at' => $sync->getLastAttemptAt(),
                'next_attempt_at' => $sync->getNextAttemptAt(),
                'erp_reference' => $sync->getErpReference(),
                'last_error' => $sync->getLastError(),
                'created_at' => $sync->getCreatedAt(),
                'updated_at' => $sync->getUpdatedAt()
            ]);

        } catch (NoSuchEntityException $e) {
            return $this->syncStatusResponseFactory->create([
                'order_increment_id' => $orderIncrementId,
                'status' => 'not_synced',
                'message' => 'No sync record found for this order'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error getting sync status: ' . $e->getMessage(), [
                'order_increment_id' => $orderIncrementId,
                'exception' => $e
            ]);

            return $this->syncStatusResponseFactory->create([
                'order_increment_id' => $orderIncrementId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function processWebhook(
        string $orderIncrementId,
        string $erpReference,
        string $status,
        ?string $signature = null
    ): WebhookResponseInterface {
        if (!$this->config->isEnabled()) {
            throw new LocalizedException(__('ERP Connector is not enabled'));
        }

        try {
            // Verify signature if provided
            if ($signature !== null) {
                $hmacSecret = $this->config->getHmacSecret();
                if (!empty($hmacSecret)) {
                    $expectedSignature = base64_encode(
                        hash_hmac('sha256', $orderIncrementId . $erpReference . $status, $hmacSecret, true)
                    );

                    if ($signature !== $expectedSignature) {
                        throw new LocalizedException(__('Invalid webhook signature'));
                    }
                }
            }

            // Get sync record
            $sync = $this->syncRepository->getByOrderIncrementId($orderIncrementId);

            // Update sync record based on webhook status
            if ($status === 'accepted' || $status === 'success') {
                $sync->setStatus(\Escooter\ErpConnector\Api\Data\SyncInterface::STATUS_SUCCESS);
                $sync->setErpReference($erpReference);
                $sync->setLastError(null);

                // Update order erp_synced flag
                try {
                    $order = $this->orderRepository->get($sync->getOrderId());
                    $order->setData('erp_synced', 1);
                    $this->orderRepository->save($order);
                } catch (\Exception $e) {
                    $this->logger->warning('Could not update order erp_synced flag: ' . $e->getMessage());
                }

            } elseif ($status === 'rejected' || $status === 'failed') {
                $sync->setStatus(\Escooter\ErpConnector\Api\Data\SyncInterface::STATUS_FAILED);
                $sync->setErpReference($erpReference);
            }

            $this->syncRepository->save($sync);

            $this->logger->info('Webhook processed successfully', [
                'order_increment_id' => $orderIncrementId,
                'erp_reference' => $erpReference,
                'status' => $status
            ]);

            return $this->webhookResponseFactory->create([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'order_increment_id' => $orderIncrementId,
                'erp_reference' => $erpReference
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing webhook: ' . $e->getMessage(), [
                'order_increment_id' => $orderIncrementId,
                'erp_reference' => $erpReference,
                'exception' => $e
            ]);

            return $this->webhookResponseFactory->create([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    /**
     * Create search criteria for order
     *
     * @param string $orderIncrementId
     * @return \Magento\Framework\Api\SearchCriteriaInterface
     */
    private function createSearchCriteria(string $orderIncrementId): \Magento\Framework\Api\SearchCriteriaInterface
    {
        return $this->searchCriteriaBuilder
            ->addFilter('increment_id', $orderIncrementId, 'eq')
            ->setPageSize(1)
            ->create();
    }
}
