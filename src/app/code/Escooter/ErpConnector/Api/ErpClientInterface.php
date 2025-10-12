<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Api;

use Escooter\ErpConnector\Api\Data\ErpResponseInterface;

interface ErpClientInterface
{
    /**
     * Send order data to ERP
     *
     * @param array $payload
     * @param string $idempotencyKey
     * @return ErpResponseInterface
     * @throws \Exception
     */
    public function sendOrder(array $payload, string $idempotencyKey): ErpResponseInterface;

    /**
     * Test ERP connectivity
     *
     * @return bool
     */
    public function testConnection(): bool;
}

