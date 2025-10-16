<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Api\Data;

interface WebhookResponseInterface
{
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const ORDER_INCREMENT_ID = 'order_increment_id';
    const ERP_REFERENCE = 'erp_reference';

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
     * Get order increment ID
     *
     * @return string|null
     */
    public function getOrderIncrementId(): ?string;

    /**
     * Set order increment ID
     *
     * @param string|null $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId(?string $orderIncrementId);

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
}
