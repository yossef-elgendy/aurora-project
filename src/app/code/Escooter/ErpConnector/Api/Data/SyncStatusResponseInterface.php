<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Api\Data;

interface SyncStatusResponseInterface
{
    public const SYNC_ID = 'sync_id';
    public const ORDER_ID = 'order_id';
    public const ORDER_INCREMENT_ID = 'order_increment_id';
    public const STATUS = 'status';
    public const ATTEMPTS = 'attempts';
    public const MAX_ATTEMPTS = 'max_attempts';
    public const LAST_ATTEMPT_AT = 'last_attempt_at';
    public const NEXT_ATTEMPT_AT = 'next_attempt_at';
    public const ERP_REFERENCE = 'erp_reference';
    public const LAST_ERROR = 'last_error';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const MESSAGE = 'message';
    public const ERROR = 'error';

    /**
     * Get sync ID
     *
     * @return int|null
     */
    public function getSyncId(): ?int;

    /**
     * Set sync ID
     *
     * @param int|null $syncId
     * @return $this
     */
    public function setSyncId(?int $syncId);

    /**
     * Get order ID
     *
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * Set order ID
     *
     * @param int|null $orderId
     * @return $this
     */
    public function setOrderId(?int $orderId);

    /**
     * Get order increment ID
     *
     * @return string
     */
    public function getOrderIncrementId(): string;

    /**
     * Set order increment ID
     *
     * @param string $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId(string $orderIncrementId);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status
     *
     * @param string|null $status
     * @return $this
     */
    public function setStatus(?string $status);

    /**
     * Get attempts count
     *
     * @return int|null
     */
    public function getAttempts(): ?int;

    /**
     * Set attempts count
     *
     * @param int|null $attempts
     * @return $this
     */
    public function setAttempts(?int $attempts);

    /**
     * Get max attempts
     *
     * @return int|null
     */
    public function getMaxAttempts(): ?int;

    /**
     * Set max attempts
     *
     * @param int|null $maxAttempts
     * @return $this
     */
    public function setMaxAttempts(?int $maxAttempts);

    /**
     * Get last attempt at
     *
     * @return string|null
     */
    public function getLastAttemptAt(): ?string;

    /**
     * Set last attempt at
     *
     * @param string|null $lastAttemptAt
     * @return $this
     */
    public function setLastAttemptAt(?string $lastAttemptAt);

    /**
     * Get next attempt at
     *
     * @return string|null
     */
    public function getNextAttemptAt(): ?string;

    /**
     * Set next attempt at
     *
     * @param string|null $nextAttemptAt
     * @return $this
     */
    public function setNextAttemptAt(?string $nextAttemptAt);

    /**
     * Get ERP reference
     *
     * @return string|null
     */
    public function getErpReference(): ?string;

    /**
     * Set ERP reference
     *
     * @param string|null $erpReference
     * @return $this
     */
    public function setErpReference(?string $erpReference);

    /**
     * Get last error
     *
     * @return string|null
     */
    public function getLastError(): ?string;

    /**
     * Set last error
     *
     * @param string|null $lastError
     * @return $this
     */
    public function setLastError(?string $lastError);

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?string $createdAt);

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at
     *
     * @param string|null $updatedAt
     * @return $this
     */
    public function setUpdatedAt(?string $updatedAt);

    /**
     * Get message
     *
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * Set message
     *
     * @param string|null $message
     * @return $this
     */
    public function setMessage(?string $message);

    /**
     * Get error
     *
     * @return string|null
     */
    public function getError(): ?string;

    /**
     * Set error
     *
     * @param string|null $error
     * @return $this
     */
    public function setError(?string $error);
}
