<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model\ResourceModel\Sync;

use Escooter\ErpConnector\Model\Sync as SyncModel;
use Escooter\ErpConnector\Model\ResourceModel\Sync as SyncResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'sync_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(SyncModel::class, SyncResource::class);
    }
}

