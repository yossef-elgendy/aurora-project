<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model\Response;

use Escooter\ErpConnector\Api\Data\WebhookResponseInterface;

class WebhookResponse implements WebhookResponseInterface
{
    /**
     * @var bool
     */
    private $success;

    /**
     * @var string|null
     */
    private $message;

    /**
     * @var string|null
     */
    private $orderIncrementId;

    /**
     * @var string|null
     */
    private $erpReference;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->success = $data[self::SUCCESS] ?? false;
        $this->message = $data[self::MESSAGE] ?? null;
        $this->orderIncrementId = $data[self::ORDER_INCREMENT_ID] ?? null;
        $this->erpReference = $data[self::ERP_REFERENCE] ?? null;
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
    public function getOrderIncrementId(): ?string
    {
        return $this->orderIncrementId;
    }

    /**
     * @inheritdoc
     */
    public function setOrderIncrementId(?string $orderIncrementId)
    {
        $this->orderIncrementId = $orderIncrementId;
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
}
