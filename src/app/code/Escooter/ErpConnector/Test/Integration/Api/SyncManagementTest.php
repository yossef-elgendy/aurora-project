<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Test\Integration\Api;

use Escooter\ErpConnector\Api\SyncManagementInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class SyncManagementTest extends WebapiAbstract
{
    const SERVICE_VERSION = 'V1';
    const SERVICE_NAME = 'erpconnectorSyncManagementV1';
    const RESOURCE_PATH = '/V1/erpconnector';

    /**
     * @var SyncManagementInterface
     */
    private $syncManagement;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        $this->syncManagement = $objectManager->get(SyncManagementInterface::class);
    }

    /**
     * Test mock update stock endpoint
     */
    public function testMockUpdateStock()
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/mock-update-stock',
                'httpMethod' => Request::HTTP_METHOD_POST
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'MockUpdateStock'
            ]
        ];

        $requestData = [
            'items' => [
                ['sku' => 'TEST-SKU-001', 'qty' => 5],
                ['sku' => 'TEST-SKU-002', 'qty' => 10]
            ],
            'orderIncrementId' => '000000999',
            'idempotencyKey' => 'test-key-999'
        ];

        $response = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertTrue($response['ok']);
        $this->assertEquals('000000999', $response['order_increment_id']);
        $this->assertEquals('test-key-999', $response['idempotency_key']);
        $this->assertArrayHasKey('erp_reference', $response);
        $this->assertCount(2, $response['items']);
    }

    /**
     * Test sync status for non-existent order
     */
    public function testGetSyncStatusForNonExistentOrder()
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/sync-status/999999999',
                'httpMethod' => Request::HTTP_METHOD_GET
            ]
        ];

        $response = $this->_webApiCall($serviceInfo);

        $this->assertEquals('not_synced', $response['status']);
        $this->assertEquals('999999999', $response['order_increment_id']);
    }

    /**
     * Test direct service call for mock update stock
     */
    public function testMockUpdateStockDirectCall()
    {
        $items = [
            ['sku' => 'PROD-123', 'qty' => 3]
        ];
        $orderIncrementId = '000000TEST';
        $idempotencyKey = 'test-idempotency-key';

        $result = $this->syncManagement->mockUpdateStock($items, $orderIncrementId, $idempotencyKey);

        $this->assertTrue($result['ok']);
        $this->assertEquals('Stock updated successfully (mock)', $result['message']);
        $this->assertEquals($orderIncrementId, $result['order_increment_id']);
        $this->assertEquals($idempotencyKey, $result['idempotency_key']);
        $this->assertNotEmpty($result['erp_reference']);
        $this->assertStringStartsWith('ERP-', $result['erp_reference']);
    }

    /**
     * Test webhook processing
     */
    public function testProcessWebhook()
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/webhook',
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $requestData = [
            'orderIncrementId' => '000000TEST',
            'erpReference' => 'ERP-TEST-123',
            'status' => 'accepted',
            'signature' => null
        ];

        $response = $this->_webApiCall($serviceInfo, $requestData);

        // Should return success even if order doesn't exist (just logs warning)
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('message', $response);
    }
}

