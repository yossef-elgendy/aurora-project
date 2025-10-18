<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model;

use Escooter\ErpConnector\Api\Data\ErpResponseInterface;

class ErpResponse implements ErpResponseInterface
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $data;

    /**
     * @param int $statusCode
     * @param string $body
     */
    public function __construct(int $statusCode, string $body)
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->data = $this->parseBody($body);
    }

    /**
     * Parse response body
     *
     * @param string $body
     * @return array
     */
    private function parseBody(string $body): array
    {
        if (empty($body)) {
            return [];
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['raw' => $body];
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritdoc
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function getErpId(): ?string
    {
        return $this->data['erp_reference'] ?? $this->data['erp_id'] ?? $this->data['id'] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getErrorMessage(): ?string
    {
        if ($this->isSuccessful()) {
            return null;
        }

        return $this->data['error'] ?? $this->data['message'] ?? $this->body;
    }

    /**
     * @inheritdoc
     */
    public function isRetryable(): bool
    {
        // 5xx errors are retryable
        if ($this->statusCode >= 500 && $this->statusCode < 600) {
            return true;
        }

        // 429 Too Many Requests is retryable
        if ($this->statusCode === 429) {
            return true;
        }

        // 4xx errors (except 429) are not retryable
        if ($this->statusCode >= 400 && $this->statusCode < 500) {
            return false;
        }

        // Network errors or timeouts (status 0) are retryable
        if ($this->statusCode === 0) {
            return true;
        }

        return false;
    }
}
