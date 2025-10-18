<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Test\Unit\Model;

use Escooter\ErpConnector\Helper\Config;
use Escooter\ErpConnector\Model\ErpClient;
use Escooter\ErpConnector\Model\ErpResponseFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ErpClientTest extends TestCase
{
    /**
     * @var ErpClient
     */
    private $erpClient;

    /**
     * @var Curl|MockObject
     */
    private $curlMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var Json|MockObject
     */
    private $jsonSerializerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ErpResponseFactory|MockObject
     */
    private $erpResponseFactoryMock;

    protected function setUp(): void
    {
        $this->curlMock = $this->createMock(Curl::class);
        $this->configMock = $this->createMock(Config::class);
        $this->jsonSerializerMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->erpResponseFactoryMock = $this->createMock(ErpResponseFactory::class);

        $this->erpClient = new ErpClient(
            $this->curlMock,
            $this->configMock,
            $this->jsonSerializerMock,
            $this->loggerMock,
            $this->erpResponseFactoryMock
        );
    }

    public function testSendOrderSuccess()
    {
        // Arrange
        $payload = [
            'order_increment_id' => '000000123',
            'customer_email' => 'test@example.com'
        ];
        $idempotencyKey = 'test-key-123';
        $baseUrl = 'https://erp.example.com/api';
        
        $this->configMock->method('getErpApiBaseUrl')->willReturn($baseUrl);
        $this->configMock->method('getErpApiKey')->willReturn('test-api-key');
        $this->configMock->method('getApiTimeout')->willReturn(30);
        $this->configMock->method('getHmacSecret')->willReturn('');
        $this->configMock->method('isDebugEnabled')->willReturn(false);

        $this->jsonSerializerMock->method('serialize')->willReturn(json_encode($payload));

        $this->curlMock->expects($this->once())
            ->method('post')
            ->with($baseUrl . '/orders/sync', json_encode($payload));

        $this->curlMock->method('getStatus')->willReturn(200);
        $this->curlMock->method('getBody')->willReturn('{"erp_reference":"ERP-123"}');

        $responseMock = $this->createMock(\Escooter\ErpConnector\Api\Data\ErpResponseInterface::class);
        $this->erpResponseFactoryMock->method('create')->willReturn($responseMock);

        // Act
        $result = $this->erpClient->sendOrder($payload, $idempotencyKey);

        // Assert
        $this->assertInstanceOf(\Escooter\ErpConnector\Api\Data\ErpResponseInterface::class, $result);
    }

    public function testSendOrderThrowsExceptionWhenBaseUrlNotConfigured()
    {
        // Arrange
        $this->configMock->method('getErpApiBaseUrl')->willReturn('');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ERP API Base URL is not configured');

        // Act
        $this->erpClient->sendOrder([], 'test-key');
    }

    public function testSendOrderHandlesException()
    {
        // Arrange
        $this->configMock->method('getErpApiBaseUrl')->willReturn('https://erp.example.com/api');
        $this->configMock->method('getErpApiKey')->willReturn('test-key');
        $this->configMock->method('getApiTimeout')->willReturn(30);
        $this->configMock->method('getHmacSecret')->willReturn('');
        $this->configMock->method('isDebugEnabled')->willReturn(false);

        $this->jsonSerializerMock->method('serialize')->willReturn('{}');

        $this->curlMock->method('post')->willThrowException(new \Exception('Connection failed'));

        $responseMock = $this->createMock(\Escooter\ErpConnector\Api\Data\ErpResponseInterface::class);
        $this->erpResponseFactoryMock->method('create')->willReturn($responseMock);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('ERP API Error'));

        // Act
        $result = $this->erpClient->sendOrder([], 'test-key');

        // Assert
        $this->assertInstanceOf(\Escooter\ErpConnector\Api\Data\ErpResponseInterface::class, $result);
    }

    public function testTestConnectionSuccess()
    {
        // Arrange
        $this->configMock->method('getErpApiBaseUrl')->willReturn('https://erp.example.com/api');
        $this->curlMock->method('getStatus')->willReturn(200);

        // Act
        $result = $this->erpClient->testConnection();

        // Assert
        $this->assertTrue($result);
    }

    public function testTestConnectionFailsWhenNoBaseUrl()
    {
        // Arrange
        $this->configMock->method('getErpApiBaseUrl')->willReturn('');

        // Act
        $result = $this->erpClient->testConnection();

        // Assert
        $this->assertFalse($result);
    }

    public function testSendOrderWithHmacSignature()
    {
        // Arrange
        $payload = ['order_increment_id' => '000000123'];
        $idempotencyKey = 'test-key-123';
        $hmacSecret = 'secret-key';
        
        $this->configMock->method('getErpApiBaseUrl')->willReturn('https://erp.example.com/api');
        $this->configMock->method('getErpApiKey')->willReturn('api-key');
        $this->configMock->method('getApiTimeout')->willReturn(30);
        $this->configMock->method('getHmacSecret')->willReturn($hmacSecret);
        $this->configMock->method('isDebugEnabled')->willReturn(false);

        $payloadJson = json_encode($payload);
        $this->jsonSerializerMock->method('serialize')->willReturn($payloadJson);

        $expectedSignature = base64_encode(hash_hmac('sha256', $payloadJson, $hmacSecret, true));

        $this->curlMock->expects($this->once())
            ->method('setHeaders')
            ->with($this->callback(function ($headers) use ($expectedSignature) {
                return isset($headers['X-Signature']) && $headers['X-Signature'] === $expectedSignature;
            }));

        $this->curlMock->method('getStatus')->willReturn(200);
        $this->curlMock->method('getBody')->willReturn('{}');

        $responseMock = $this->createMock(\Escooter\ErpConnector\Api\Data\ErpResponseInterface::class);
        $this->erpResponseFactoryMock->method('create')->willReturn($responseMock);

        // Act
        $this->erpClient->sendOrder($payload, $idempotencyKey);
    }
}
