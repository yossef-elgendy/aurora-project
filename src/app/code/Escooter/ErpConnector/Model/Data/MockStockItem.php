<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model\Data;

use Escooter\ErpConnector\Api\Data\MockStockItemInterface;
use Magento\Framework\DataObject;

class MockStockItem extends DataObject implements MockStockItemInterface
{
    /**
     * @inheritdoc
     */
    public function getSku(): string
    {
        return (string) $this->getData('sku');
    }

    /**
     * @inheritdoc
     */
    public function setSku(string $sku)
    {
        return $this->setData('sku', $sku);
    }

    /**
     * @inheritdoc
     */
    public function getQty(): int
    {
        return (int) $this->getData('qty');
    }

    /**
     * @inheritdoc
     */
    public function setQty(int $qty)
    {
        return $this->setData('qty', $qty);
    }
}
