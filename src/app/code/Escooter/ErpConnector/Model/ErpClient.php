<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Model;

use Escooter\ErpConnector\Api\Data\ErpResponseInterface;
use Escooter\ErpConnector\Api\ErpClientInterface;
use Escooter\ErpConnector\Helper\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class ErpClient implements ErpClientInterface
{
    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ErpResponseFactory
     */
    private $erpResponseFactory;

    /**
     * @param Curl $curl
     * @param Config $config
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     * @param ErpResponseFactory $erpResponseFactory
     */
    public function __construct(
        Curl $curl,
        Config $config,
        Json $jsonSerializer,
        LoggerInterface $logger,
        ErpResponseFactory $erpResponseFactory
    ) {
        $this->curl = $curl;
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->erpResponseFactory = $erpResponseFactory;
    }

    /**
     * @inheritdoc
     */
    public function sendOrder(array $payload, string $idempotencyKey): ErpResponseInterface
    {
        $url = $this->config->getErpApiBaseUrl();
        if (empty($url)) {
            throw new \RuntimeException('ERP API Base URL is not configured');
        }

        $endpoint = rtrim($url, '/') . '/orders/sync';
        $apiKey = $this->config->getErpApiKey();
        $timeout = $this->config->getApiTimeout();

        try {
            // Prepare headers
            $headers = [
                'Content-Type' => 'application/json',
                'X-Idempotency-Key' => $idempotencyKey
            ];

            if (!empty($apiKey)) {
                $headers['X-API-KEY'] = $apiKey;
            }

            // Add HMAC signature if secret is configured
            $hmacSecret = $this->config->getHmacSecret();
            if (!empty($hmacSecret)) {
                $payloadJson = $this->jsonSerializer->serialize($payload);
                $signature = base64_encode(hash_hmac('sha256', $payloadJson, $hmacSecret, true));
                $headers['X-Signature'] = $signature;
            }

            // Set headers
            $this->curl->setHeaders($headers);

            // Set timeout
            $this->curl->setTimeout($timeout);

            // Log request if debug is enabled
            if ($this->config->isDebugEnabled()) {
                $this->logger->debug('ERP API Request', [
                    'endpoint' => $endpoint,
                    'idempotency_key' => $idempotencyKey,
                    'payload' => $payload
                ]);
            }

            // Make the request
            $payloadJson = $this->jsonSerializer->serialize($payload);
            $this->curl->post($endpoint, $payloadJson);

            $statusCode = $this->curl->getStatus();
            $body = $this->curl->getBody();

            // Log response if debug is enabled
            if ($this->config->isDebugEnabled()) {
                $this->logger->debug('ERP API Response', [
                    'status_code' => $statusCode,
                    'body' => $body
                ]);
            }

            return $this->erpResponseFactory->create([
                'statusCode' => $statusCode,
                'body' => $body
            ]);

        } catch (\Exception $e) {
            $this->logger->error('ERP API Error: ' . $e->getMessage(), [
                'exception' => $e,
                'endpoint' => $endpoint ?? null,
                'idempotency_key' => $idempotencyKey
            ]);

            // Return error response
            return $this->erpResponseFactory->create([
                'statusCode' => 0,
                'body' => $e->getMessage()
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function testConnection(): bool
    {
        $url = $this->config->getErpApiBaseUrl();
        if (empty($url)) {
            return false;
        }

        try {
            $endpoint = rtrim($url, '/') . '/health';
            $this->curl->setTimeout(5);
            $this->curl->get($endpoint);
            $statusCode = $this->curl->getStatus();

            return $statusCode >= 200 && $statusCode < 300;
        } catch (\Exception $e) {
            $this->logger->error('ERP connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}

