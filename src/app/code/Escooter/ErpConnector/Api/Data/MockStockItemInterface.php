<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Api\Data;

interface MockStockItemInterface
{
    /**
     * Get SKU
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku);

    /**
     * Get quantity
     *
     * @return int
     */
    public function getQty(): int;

    /**
     * Set quantity
     *
     * @param int $qty
     * @return $this
     */
    public function setQty(int $qty);
}

