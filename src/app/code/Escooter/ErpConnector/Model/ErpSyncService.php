<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model;

use Escooter\ErpConnector\Api\Data\SyncInterface;
use Escooter\ErpConnector\Api\ErpClientInterface;
use Escooter\ErpConnector\Api\SyncRepositoryInterface;
use Escooter\ErpConnector\Helper\Config;
use Escooter\ErpConnector\Helper\IdempotencyKeyGenerator;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class ErpSyncService
{
    /**
     * @var SyncRepositoryInterface
     */
    private $syncRepository;

    /**
     * @var ErpClientInterface
     */
    private $erpClient;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var IdempotencyKeyGenerator
     */
    private $idempotencyKeyGenerator;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SyncFactory
     */
    private $syncFactory;

    /**
     * @param SyncRepositoryInterface $syncRepository
     * @param ErpClientInterface $erpClient
     * @param OrderRepositoryInterface $orderRepository
     * @param Config $config
     * @param IdempotencyKeyGenerator $idempotencyKeyGenerator
     * @param DateTime $dateTime
     * @param Json $jsonSerializer
     * @param EventManager $eventManager
     * @param LoggerInterface $logger
     * @param SyncFactory $syncFactory
     */
    public function __construct(
        SyncRepositoryInterface $syncRepository,
        ErpClientInterface $erpClient,
        OrderRepositoryInterface $orderRepository,
        Config $config,
        IdempotencyKeyGenerator $idempotencyKeyGenerator,
        DateTime $dateTime,
        Json $jsonSerializer,
        EventManager $eventManager,
        LoggerInterface $logger,
        SyncFactory $syncFactory
    ) {
        $this->syncRepository = $syncRepository;
        $this->erpClient = $erpClient;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->idempotencyKeyGenerator = $idempotencyKeyGenerator;
        $this->dateTime = $dateTime;
        $this->jsonSerializer = $jsonSerializer;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->syncFactory = $syncFactory;
    }

    /**
     * Create sync record for order
     *
     * @param OrderInterface $order
     * @return SyncInterface
     * @throws \Exception
     */
    public function createForOrder(OrderInterface $order): SyncInterface
    {
        // Check if sync record already exists
        try {
            return $this->syncRepository->getByOrderId((int) $order->getEntityId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Create new sync record
            $sync = $this->syncFactory->create();
            $sync->setOrderId((int) $order->getEntityId());
            $sync->setOrderIncrementId($order->getIncrementId());
            $sync->setStatus(SyncInterface::STATUS_PENDING);
            $sync->setAttempts(0);
            $sync->setMaxAttempts($this->config->getMaxAttempts());
            $sync->setIdempotencyKey($this->idempotencyKeyGenerator->generate($order));

            return $this->syncRepository->save($sync);
        }
    }

    /**
     * Process sync for a sync record
     *
     * @param SyncInterface $sync
     * @return bool
     */
    public function processSync(SyncInterface $sync): bool
    {
        // Skip if already successful
        if ($sync->getStatus() === SyncInterface::STATUS_SUCCESS) {
            return true;
        }

        // Set status to in progress
        $sync->setStatus(SyncInterface::STATUS_IN_PROGRESS);
        $this->syncRepository->save($sync);

        try {
            // Load order
            $order = $this->orderRepository->get($sync->getOrderId());

            // Build payload
            $payload = $this->buildPayload($order);

            // Get or generate idempotency key
            $idempotencyKey = $sync->getIdempotencyKey();
            if (empty($idempotencyKey)) {
                $idempotencyKey = $this->idempotencyKeyGenerator->generate($order);
                $sync->setIdempotencyKey($idempotencyKey);
            }

            // Save payload
            $sync->setPayload($this->jsonSerializer->serialize($payload));

            // Dispatch before send event
            $this->eventManager->dispatch('vendor_erp_sync_before_send', [
                'sync' => $sync,
                'order' => $order,
                'payload' => $payload
            ]);

            // Send to ERP
            $response = $this->erpClient->sendOrder($payload, $idempotencyKey);

            // Increment attempts
            $sync->setAttempts($sync->getAttempts() + 1);
            $sync->setLastAttemptAt($this->dateTime->gmtDate());
            $sync->setResponse($response->getBody());

            // Dispatch after send event
            $this->eventManager->dispatch('vendor_erp_sync_after_send', [
                'sync' => $sync,
                'order' => $order,
                'response' => $response
            ]);

            if ($response->isSuccessful()) {
                return $this->handleSuccess($sync, $order, $response);
            } else {
                return $this->handleFailure($sync, $order, $response);
            }

        } catch (\Exception $e) {
            $this->logger->error('ERP Sync Error: ' . $e->getMessage(), [
                'exception' => $e,
                'sync_id' => $sync->getSyncId(),
                'order_id' => $sync->getOrderId()
            ]);

            $sync->setAttempts($sync->getAttempts() + 1);
            $sync->setLastAttemptAt($this->dateTime->gmtDate());
            $sync->setLastError($e->getMessage());

            return $this->scheduleRetry($sync);
        }
    }

    /**
     * Handle successful sync
     *
     * @param SyncInterface $sync
     * @param OrderInterface $order
     * @param \Escooter\ErpConnector\Api\Data\ErpResponseInterface $response
     * @return bool
     */
    private function handleSuccess(
        SyncInterface $sync,
        OrderInterface $order,
        \Escooter\ErpConnector\Api\Data\ErpResponseInterface $response
    ): bool {
        $sync->setStatus(SyncInterface::STATUS_SUCCESS);
        $sync->setErpReference($response->getErpId());
        $sync->setLastError(null);
        $this->syncRepository->save($sync);

        // Update order erp_synced flag
        try {
            $orderToUpdate = $this->orderRepository->get($order->getEntityId());
            $orderToUpdate->setData('erp_synced', 1);
            $this->orderRepository->save($orderToUpdate);
        } catch (\Exception $e) {
            $this->logger->warning('Could not update order erp_synced flag: ' . $e->getMessage());
        }

        $this->logger->info('Order synced successfully to ERP', [
            'order_increment_id' => $order->getIncrementId(),
            'erp_reference' => $response->getErpId()
        ]);

        // Dispatch success event
        $this->eventManager->dispatch('vendor_erp_sync_success', [
            'sync' => $sync,
            'order' => $order,
            'response' => $response
        ]);

        return true;
    }

    /**
     * Handle failed sync
     *
     * @param SyncInterface $sync
     * @param OrderInterface $order
     * @param \Escooter\ErpConnector\Api\Data\ErpResponseInterface $response
     * @return bool
     */
    private function handleFailure(
        SyncInterface $sync,
        OrderInterface $order,
        \Escooter\ErpConnector\Api\Data\ErpResponseInterface $response
    ): bool {
        $errorMessage = $response->getErrorMessage();
        $sync->setLastError($errorMessage);

        $this->logger->warning('Order sync failed', [
            'order_increment_id' => $order->getIncrementId(),
            'status_code' => $response->getStatusCode(),
            'error' => $errorMessage,
            'attempts' => $sync->getAttempts()
        ]);

        // Check if retryable
        if (!$response->isRetryable()) {
            $sync->setStatus(SyncInterface::STATUS_FAILED);
            $this->syncRepository->save($sync);

            $this->logger->error('Order sync failed permanently (non-retryable error)', [
                'order_increment_id' => $order->getIncrementId(),
                'error' => $errorMessage
            ]);

            // Dispatch failed event
            $this->eventManager->dispatch('vendor_erp_sync_failed', [
                'sync' => $sync,
                'order' => $order,
                'response' => $response
            ]);

            return false;
        }

        return $this->scheduleRetry($sync);
    }

    /**
     * Schedule retry with exponential backoff
     *
     * @param SyncInterface $sync
     * @return bool
     */
    private function scheduleRetry(SyncInterface $sync): bool
    {
        // Check if max attempts reached
        if ($sync->getAttempts() >= $sync->getMaxAttempts()) {
            $sync->setStatus(SyncInterface::STATUS_FAILED);
            $this->syncRepository->save($sync);

            $this->logger->error('Order sync failed after max attempts', [
                'order_increment_id' => $sync->getOrderIncrementId(),
                'attempts' => $sync->getAttempts(),
                'max_attempts' => $sync->getMaxAttempts()
            ]);

            // Dispatch failed event
            $this->eventManager->dispatch('vendor_erp_sync_failed', [
                'sync' => $sync
            ]);

            return false;
        }

        // Calculate exponential backoff delay
        $baseDelay = $this->config->getBaseDelay();
        $delay = pow(2, $sync->getAttempts()) * $baseDelay;
        $nextAttemptAt = $this->dateTime->gmtDate('Y-m-d H:i:s', time() + $delay);

        $sync->setStatus(SyncInterface::STATUS_QUEUED);
        $sync->setNextAttemptAt($nextAttemptAt);
        $this->syncRepository->save($sync);

        $this->logger->info('Order sync scheduled for retry', [
            'order_increment_id' => $sync->getOrderIncrementId(),
            'attempts' => $sync->getAttempts(),
            'next_attempt_at' => $nextAttemptAt,
            'delay_seconds' => $delay
        ]);

        return false;
    }

    /**
     * Build payload for ERP
     *
     * @param OrderInterface $order
     * @return array
     */
    private function buildPayload(OrderInterface $order): array
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            // Skip configurable parent items
            if ($item->getParentItemId()) {
                continue;
            }

            $items[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'qty' => $item->getQtyOrdered(),
                'price' => $item->getPrice(),
                'row_total' => $item->getRowTotal()
            ];
        }

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getIsVirtual() ? null : $order->getShippingAddress();

        return [
            'order_increment_id' => $order->getIncrementId(),
            'order_id' => $order->getEntityId(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_firstname' => $order->getCustomerFirstname(),
            'customer_lastname' => $order->getCustomerLastname(),
            'items' => $items,
            'totals' => [
                'subtotal' => $order->getSubtotal(),
                'tax' => $order->getTaxAmount(),
                'shipping' => $order->getShippingAmount(),
                'discount' => $order->getDiscountAmount(),
                'grand_total' => $order->getGrandTotal()
            ],
            'billing_address' => $billingAddress ? [
                'firstname' => $billingAddress->getFirstname(),
                'lastname' => $billingAddress->getLastname(),
                'street' => $billingAddress->getStreet(),
                'city' => $billingAddress->getCity(),
                'region' => $billingAddress->getRegion(),
                'postcode' => $billingAddress->getPostcode(),
                'country_id' => $billingAddress->getCountryId(),
                'telephone' => $billingAddress->getTelephone()
            ] : null,
            'shipping_address' => $shippingAddress ? [
                'firstname' => $shippingAddress->getFirstname(),
                'lastname' => $shippingAddress->getLastname(),
                'street' => $shippingAddress->getStreet(),
                'city' => $shippingAddress->getCity(),
                'region' => $shippingAddress->getRegion(),
                'postcode' => $shippingAddress->getPostcode(),
                'country_id' => $shippingAddress->getCountryId(),
                'telephone' => $shippingAddress->getTelephone()
            ] : null,
            'created_at' => $order->getCreatedAt(),
            'updated_at' => $order->getUpdatedAt()
        ];
    }

    /**
     * Enqueue order for sync
     *
     * @param OrderInterface $order
     * @return SyncInterface
     * @throws \Exception
     */
    public function enqueue(OrderInterface $order): SyncInterface
    {
        $sync = $this->createForOrder($order);
        $sync->setStatus(SyncInterface::STATUS_QUEUED);
        $sync->setNextAttemptAt($this->dateTime->gmtDate());
        return $this->syncRepository->save($sync);
    }

    /**
     * Reschedule sync for immediate retry
     *
     * @param SyncInterface $sync
     * @return SyncInterface
     * @throws \Exception
     */
    public function reschedule(SyncInterface $sync): SyncInterface
    {
        $sync->setStatus(SyncInterface::STATUS_QUEUED);
        $sync->setNextAttemptAt($this->dateTime->gmtDate());
        $sync->setLastError(null);
        return $this->syncRepository->save($sync);
    }
}

