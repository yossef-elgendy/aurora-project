<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Observer;

use Escooter\ErpConnector\Helper\Config;
use Escooter\ErpConnector\Model\ErpSyncService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class InvoiceCreatedObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ErpSyncService
     */
    private $erpSyncService;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Config $config
     * @param ErpSyncService $erpSyncService
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        ErpSyncService $erpSyncService,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->erpSyncService = $erpSyncService;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        // Check if module is enabled
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            $invoice = $observer->getEvent()->getInvoice();

            if (!$invoice || !$invoice->getOrderId()) {
                return;
            }

            // Load order
            $order = $this->orderRepository->get($invoice->getOrderId());

            // Check if order is already synced
            if ($order->getErpSynced() == 1) {
                return;
            }

            // Create sync record
            $sync = $this->erpSyncService->createForOrder($order);

            // If immediate sync is enabled, process immediately
            if ($this->config->isImmediateSyncEnabled()) {
                $this->erpSyncService->processSync($sync);
            } else {
                // Otherwise, enqueue for cron processing
                $this->erpSyncService->enqueue($order);
            }

        } catch (\Exception $e) {
            // Log error but don't throw to avoid breaking invoice creation
            $this->logger->error('Error in InvoiceCreatedObserver: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }
}

