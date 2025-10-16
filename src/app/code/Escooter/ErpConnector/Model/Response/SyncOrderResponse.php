<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model\Response;

use Escooter\ErpConnector\Api\Data\SyncOrderResponseInterface;

class SyncOrderResponse implements SyncOrderResponseInterface
{
    /**
     * @var bool
     */
    private $success;

    /**
     * @var int|null
     */
    private $syncId;

    /**
     * @var string
     */
    private $orderIncrementId;

    /**
     * @var string|null
     */
    private $status;

    /**
     * @var string|null
     */
    private $message;

    /**
     * @var string|null
     */
    private $erpReference;

    /**
     * @var int|null
     */
    private $attempts;

    /**
     * @var string|null
     */
    private $lastError;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->success = $data[self::SUCCESS] ?? false;
        $this->syncId = $data[self::SYNC_ID] ?? null;
        $this->orderIncrementId = $data[self::ORDER_INCREMENT_ID] ?? '';
        $this->status = $data[self::STATUS] ?? null;
        $this->message = $data[self::MESSAGE] ?? null;
        $this->erpReference = $data[self::ERP_REFERENCE] ?? null;
        $this->attempts = $data[self::ATTEMPTS] ?? null;
        $this->lastError = $data[self::LAST_ERROR] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @inheritdoc
     */
    public function setSuccess(bool $success)
    {
        $this->success = $success;
        return $this;
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
}
