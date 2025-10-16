<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model\Response;

use Escooter\ErpConnector\Api\Data\SyncStatusResponseInterface;

class SyncStatusResponse implements SyncStatusResponseInterface
{
    /**
     * @var int|null
     */
    private $syncId;

    /**
     * @var int|null
     */
    private $orderId;

    /**
     * @var string
     */
    private $orderIncrementId;

    /**
     * @var string|null
     */
    private $status;

    /**
     * @var int|null
     */
    private $attempts;

    /**
     * @var int|null
     */
    private $maxAttempts;

    /**
     * @var string|null
     */
    private $lastAttemptAt;

    /**
     * @var string|null
     */
    private $nextAttemptAt;

    /**
     * @var string|null
     */
    private $erpReference;

    /**
     * @var string|null
     */
    private $lastError;

    /**
     * @var string|null
     */
    private $createdAt;

    /**
     * @var string|null
     */
    private $updatedAt;

    /**
     * @var string|null
     */
    private $message;

    /**
     * @var string|null
     */
    private $error;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->syncId = $data[self::SYNC_ID] ?? null;
        $this->orderId = $data[self::ORDER_ID] ?? null;
        $this->orderIncrementId = $data[self::ORDER_INCREMENT_ID] ?? '';
        $this->status = $data[self::STATUS] ?? null;
        $this->attempts = $data[self::ATTEMPTS] ?? null;
        $this->maxAttempts = $data[self::MAX_ATTEMPTS] ?? null;
        $this->lastAttemptAt = $data[self::LAST_ATTEMPT_AT] ?? null;
        $this->nextAttemptAt = $data[self::NEXT_ATTEMPT_AT] ?? null;
        $this->erpReference = $data[self::ERP_REFERENCE] ?? null;
        $this->lastError = $data[self::LAST_ERROR] ?? null;
        $this->createdAt = $data[self::CREATED_AT] ?? null;
        $this->updatedAt = $data[self::UPDATED_AT] ?? null;
        $this->message = $data[self::MESSAGE] ?? null;
        $this->error = $data[self::ERROR] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getSyncId(): ?int
    {
        return $this->syncId;
    }

    /**
     * @inheritdoc
     */
    public function setSyncId(?int $syncId)
    {
        $this->syncId = $syncId;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    /**
     * @inheritdoc
     */
    public function setOrderId(?int $orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOrderIncrementId(): string
    {
        return $this->orderIncrementId;
    }

    /**
     * @inheritdoc
     */
    public function setOrderIncrementId(string $orderIncrementId)
    {
        $this->orderIncrementId = $orderIncrementId;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @inheritdoc
     */
    public function setStatus(?string $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAttempts(): ?int
    {
        return $this->attempts;
    }

    /**
     * @inheritdoc
     */
    public function setAttempts(?int $attempts)
    {
        $this->attempts = $attempts;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMaxAttempts(): ?int
    {
        return $this->maxAttempts;
    }

    /**
     * @inheritdoc
     */
    public function setMaxAttempts(?int $maxAttempts)
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLastAttemptAt(): ?string
    {
        return $this->lastAttemptAt;
    }

    /**
     * @inheritdoc
     */
    public function setLastAttemptAt(?string $lastAttemptAt)
    {
        $this->lastAttemptAt = $lastAttemptAt;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getNextAttemptAt(): ?string
    {
        return $this->nextAttemptAt;
    }

    /**
     * @inheritdoc
     */
    public function setNextAttemptAt(?string $nextAttemptAt)
    {
        $this->nextAttemptAt = $nextAttemptAt;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getErpReference(): ?string
    {
        return $this->erpReference;
    }

    /**
     * @inheritdoc
     */
    public function setErpReference(?string $erpReference)
    {
        $this->erpReference = $erpReference;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * @inheritdoc
     */
    public function setLastError(?string $lastError)
    {
        $this->lastError = $lastError;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(?string $updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     */
    public function setMessage(?string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function setError(?string $error)
    {
        $this->error = $error;
        return $this;
    }
}
