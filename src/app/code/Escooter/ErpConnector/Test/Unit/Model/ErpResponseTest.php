<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Test\Unit\Model;

use Escooter\ErpConnector\Model\ErpResponse;
use PHPUnit\Framework\TestCase;

class ErpResponseTest extends TestCase
{
    public function testIsSuccessfulWithSuccessCode()
    {
        $response = new ErpResponse(200, '{"status":"ok"}');
        $this->assertTrue($response->isSuccessful());
    }

    public function testIsSuccessfulWithFailureCode()
    {
        $response = new ErpResponse(500, '{"error":"server error"}');
        $this->assertFalse($response->isSuccessful());
    }

    public function testGetStatusCode()
    {
        $response = new ErpResponse(404, '{}');
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetBody()
    {
        $body = '{"test":"value"}';
        $response = new ErpResponse(200, $body);
        $this->assertEquals($body, $response->getBody());
    }

    public function testGetDataParsesJson()
    {
        $response = new ErpResponse(200, '{"erp_id":"123","status":"ok"}');
        $data = $response->getData();
        
        $this->assertIsArray($data);
        $this->assertEquals('123', $data['erp_id']);
        $this->assertEquals('ok', $data['status']);
    }

    public function testGetDataHandlesInvalidJson()
    {
        $response = new ErpResponse(200, 'invalid json');
        $data = $response->getData();
        
        $this->assertIsArray($data);
        $this->assertEquals('invalid json', $data['raw']);
    }

    public function testGetErpIdFromDifferentFields()
    {
        // Test erp_reference
        $response1 = new ErpResponse(200, '{"erp_reference":"ERP-123"}');
        $this->assertEquals('ERP-123', $response1->getErpId());

        // Test erp_id
        $response2 = new ErpResponse(200, '{"erp_id":"ERP-456"}');
        $this->assertEquals('ERP-456', $response2->getErpId());

        // Test id
        $response3 = new ErpResponse(200, '{"id":"789"}');
        $this->assertEquals('789', $response3->getErpId());

        // Test none
        $response4 = new ErpResponse(200, '{}');
        $this->assertNull($response4->getErpId());
    }

    public function testGetErrorMessageOnSuccess()
    {
        $response = new ErpResponse(200, '{"status":"ok"}');
        $this->assertNull($response->getErrorMessage());
    }

    public function testGetErrorMessageOnFailure()
    {
        $response = new ErpResponse(400, '{"error":"bad request"}');
        $this->assertEquals('bad request', $response->getErrorMessage());
    }

    public function testIsRetryableWith5xxError()
    {
        $response = new ErpResponse(500, '{}');
        $this->assertTrue($response->isRetryable());

        $response = new ErpResponse(503, '{}');
        $this->assertTrue($response->isRetryable());
    }

    public function testIsRetryableWith429Error()
    {
        $response = new ErpResponse(429, '{"error":"rate limit"}');
        $this->assertTrue($response->isRetryable());
    }

    public function testIsNotRetryableWith4xxError()
    {
        $response = new ErpResponse(400, '{"error":"bad request"}');
        $this->assertFalse($response->isRetryable());

        $response = new ErpResponse(404, '{"error":"not found"}');
        $this->assertFalse($response->isRetryable());
    }

    public function testIsRetryableWithNetworkError()
    {
        $response = new ErpResponse(0, 'Connection failed');
        $this->assertTrue($response->isRetryable());
    }

    public function testIsNotRetryableWith2xxSuccess()
    {
        $response = new ErpResponse(200, '{}');
        $this->assertFalse($response->isRetryable());

        $response = new ErpResponse(201, '{}');
        $this->assertFalse($response->isRetryable());
    }
}
