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

