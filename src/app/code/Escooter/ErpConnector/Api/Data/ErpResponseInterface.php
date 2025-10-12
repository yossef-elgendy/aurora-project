<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Api\Data;

interface ErpResponseInterface
{
    /**
     * Check if response is successful
     *
     * @return bool
     */
    public function isSuccessful(): bool;

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * Get response body
     *
     * @return string
     */
    public function getBody(): string;

    /**
     * Get parsed response data
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Get ERP reference ID
     *
     * @return string|null
     */
    public function getErpId(): ?string;

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string;

    /**
     * Check if response is retryable
     *
     * @return bool
     */
    public function isRetryable(): bool;
}

