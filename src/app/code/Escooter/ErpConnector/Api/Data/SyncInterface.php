<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Api\Data;

interface SyncInterface
{
    const SYNC_ID = 'sync_id';
    const ORDER_ID = 'order_id';
    const ORDER_INCREMENT_ID = 'order_increment_id';
    const STATUS = 'status';
    const ATTEMPTS = 'attempts';
    const MAX_ATTEMPTS = 'max_attempts';
    const LAST_ATTEMPT_AT = 'last_attempt_at';
    const NEXT_ATTEMPT_AT = 'next_attempt_at';
    const LAST_ERROR = 'last_error';
    const ERP_REFERENCE = 'erp_reference';
    const IDEMPOTENCY_KEY = 'idempotency_key';
    const PAYLOAD = 'payload';
    const RESPONSE = 'response';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    const STATUS_PENDING = 'pending';
    const STATUS_QUEUED = 'queued';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * Get sync ID
     *
     * @return int|null
     */
    public function getSyncId();

    /**
     * Set sync ID
     *
     * @param int $syncId
     * @return $this
     */
    public function setSyncId($syncId);

    /**
     * Get order ID
     *
     * @return int
     */
    public function getOrderId();

    /**
     * Set order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get order increment ID
     *
     * @return string
     */
    public function getOrderIncrementId();

    /**
     * Set order increment ID
     *
     * @param string $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId($orderIncrementId);

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get attempts
     *
     * @return int
     */
    public function getAttempts();

    /**
     * Set attempts
     *
     * @param int $attempts
     * @return $this
     */
    public function setAttempts($attempts);

    /**
     * Get max attempts
     *
     * @return int
     */
    public function getMaxAttempts();

    /**
     * Set max attempts
     *
     * @param int $maxAttempts
     * @return $this
     */
    public function setMaxAttempts($maxAttempts);

    /**
     * Get last attempt at
     *
     * @return string|null
     */
    public function getLastAttemptAt();

    /**
     * Set last attempt at
     *
     * @param string $lastAttemptAt
     * @return $this
     */
    public function setLastAttemptAt($lastAttemptAt);

    /**
     * Get next attempt at
     *
     * @return string|null
     */
    public function getNextAttemptAt();

    /**
     * Set next attempt at
     *
     * @param string $nextAttemptAt
     * @return $this
     */
    public function setNextAttemptAt($nextAttemptAt);

    /**
     * Get last error
     *
     * @return string|null
     */
    public function getLastError();

    /**
     * Set last error
     *
     * @param string $lastError
     * @return $this
     */
    public function setLastError($lastError);

    /**
     * Get ERP reference
     *
     * @return string|null
     */
    public function getErpReference();

    /**
     * Set ERP reference
     *
     * @param string $erpReference
     * @return $this
     */
    public function setErpReference($erpReference);

    /**
     * Get idempotency key
     *
     * @return string|null
     */
    public function getIdempotencyKey();

    /**
     * Set idempotency key
     *
     * @param string $idempotencyKey
     * @return $this
     */
    public function setIdempotencyKey($idempotencyKey);

    /**
     * Get payload
     *
     * @return string|null
     */
    public function getPayload();

    /**
     * Set payload
     *
     * @param string $payload
     * @return $this
     */
    public function setPayload($payload);

    /**
     * Get response
     *
     * @return string|null
     */
    public function getResponse();

    /**
     * Set response
     *
     * @param string $response
     * @return $this
     */
    public function setResponse($response);

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}
