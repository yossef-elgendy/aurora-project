<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Checkout\Plugin\Model\ResourceModel\Order\Grid;

use Magento\Framework\Data\Collection as DataCollection;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

/**
 * Plugin to add escooter_notes to order grid collection
 */
class Collection
{
    /**
     * Add escooter_notes field to order grid collection
     *
     * @param OrderGridCollection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoad(OrderGridCollection $subject, $printQuery = false, $logQuery = false)
    {
        if (!$subject->isLoaded()) {
            $select = $subject->getSelect();

            // Check if the join hasn't been added yet
            $fromParts = $select->getPart(\Magento\Framework\DB\Select::FROM);
            if (!isset($fromParts['sales_order_address'])) {
                $select->joinLeft(
                    ['soa' => $subject->getTable('sales_order_address')],
                    'main_table.entity_id = soa.parent_id AND soa.address_type = "shipping"',
                    ['escooter_notes' => 'soa.escooter_notes']
                );
            }
        }

        return [$printQuery, $logQuery];
    }
}

