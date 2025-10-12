<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model;

use Escooter\ErpConnector\Api\Data\SyncInterface;
use Magento\Framework\Model\AbstractModel;

class Sync extends AbstractModel implements SyncInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(\Escooter\ErpConnector\Model\ResourceModel\Sync::class);
    }

    /**
     * @inheritdoc
     */
    public function getSyncId()
    {
        return $this->getData(self::SYNC_ID);
    }

    /**
     * @inheritdoc
     */
    public function setSyncId($syncId)
    {
        return $this->setData(self::SYNC_ID, $syncId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderIncrementId()
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderIncrementId($orderIncrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getAttempts()
    {
        return $this->getData(self::ATTEMPTS);
    }

    /**
     * @inheritdoc
     */
    public function setAttempts($attempts)
    {
        return $this->setData(self::ATTEMPTS, $attempts);
    }

    /**
     * @inheritdoc
     */
    public function getMaxAttempts()
    {
        return $this->getData(self::MAX_ATTEMPTS);
    }

    /**
     * @inheritdoc
     */
    public function setMaxAttempts($maxAttempts)
    {
        return $this->setData(self::MAX_ATTEMPTS, $maxAttempts);
    }

    /**
     * @inheritdoc
     */
    public function getLastAttemptAt()
    {
        return $this->getData(self::LAST_ATTEMPT_AT);
    }

    /**
     * @inheritdoc
     */
    public function setLastAttemptAt($lastAttemptAt)
    {
        return $this->setData(self::LAST_ATTEMPT_AT, $lastAttemptAt);
    }

    /**
     * @inheritdoc
     */
    public function getNextAttemptAt()
    {
        return $this->getData(self::NEXT_ATTEMPT_AT);
    }

    /**
     * @inheritdoc
     */
    public function setNextAttemptAt($nextAttemptAt)
    {
        return $this->setData(self::NEXT_ATTEMPT_AT, $nextAttemptAt);
    }

    /**
     * @inheritdoc
     */
    public function getLastError()
    {
        return $this->getData(self::LAST_ERROR);
    }

    /**
     * @inheritdoc
     */
    public function setLastError($lastError)
    {
        return $this->setData(self::LAST_ERROR, $lastError);
    }

    /**
     * @inheritdoc
     */
    public function getErpReference()
    {
        return $this->getData(self::ERP_REFERENCE);
    }

    /**
     * @inheritdoc
     */
    public function setErpReference($erpReference)
    {
        return $this->setData(self::ERP_REFERENCE, $erpReference);
    }

    /**
     * @inheritdoc
     */
    public function getIdempotencyKey()
    {
        return $this->getData(self::IDEMPOTENCY_KEY);
    }

    /**
     * @inheritdoc
     */
    public function setIdempotencyKey($idempotencyKey)
    {
        return $this->setData(self::IDEMPOTENCY_KEY, $idempotencyKey);
    }

    /**
     * @inheritdoc
     */
    public function getPayload()
    {
        return $this->getData(self::PAYLOAD);
    }

    /**
     * @inheritdoc
     */
    public function setPayload($payload)
    {
        return $this->setData(self::PAYLOAD, $payload);
    }

    /**
     * @inheritdoc
     */
    public function getResponse()
    {
        return $this->getData(self::RESPONSE);
    }

    /**
     * @inheritdoc
     */
    public function setResponse($response)
    {
        return $this->setData(self::RESPONSE, $response);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}

