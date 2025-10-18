<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Api\Data;

interface SyncOrderResponseInterface
{
    public const SUCCESS = 'success';
    public const SYNC_ID = 'sync_id';
    public const ORDER_INCREMENT_ID = 'order_increment_id';
    public const STATUS = 'status';
    public const MESSAGE = 'message';
    public const ERP_REFERENCE = 'erp_reference';
    public const ATTEMPTS = 'attempts';
    public const LAST_ERROR = 'last_error';

    /**
     * Get success status
     *
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * Set success status
     *
     * @param bool $success
     * @return $this
     */
    public function setSuccess(bool $success);

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
}
