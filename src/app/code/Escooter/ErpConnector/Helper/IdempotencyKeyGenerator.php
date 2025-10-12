<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Helper;

use Magento\Sales\Api\Data\OrderInterface;

class IdempotencyKeyGenerator
{
    const PREFIX = 'ERP_';

    /**
     * Generate idempotency key for order
     *
     * @param OrderInterface $order
     * @return string
     */
    public function generate(OrderInterface $order): string
    {
        return self::PREFIX . hash(
            'sha256',
            $order->getIncrementId() . '_' . $order->getEntityId() . '_' . $order->getCreatedAt()
        );
    }

    /**
     * Generate idempotency key from order increment ID
     *
     * @param string $orderIncrementId
     * @param string|null $timestamp
     * @return string
     */
    public function generateFromIncrementId(string $orderIncrementId, ?string $timestamp = null): string
    {
        $timestamp = $timestamp ?: date('Y-m-d H:i:s');
        return self::PREFIX . hash('sha256', $orderIncrementId . '_' . $timestamp);
    }
}

