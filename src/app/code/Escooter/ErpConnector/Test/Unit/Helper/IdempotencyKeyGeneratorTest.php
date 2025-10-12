<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Test\Unit\Helper;

use Escooter\ErpConnector\Helper\IdempotencyKeyGenerator;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\TestCase;

class IdempotencyKeyGeneratorTest extends TestCase
{
    /**
     * @var IdempotencyKeyGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->generator = new IdempotencyKeyGenerator();
    }

    public function testGenerateCreatesKeyWithPrefix()
    {
        $orderMock = $this->createMock(OrderInterface::class);
        $orderMock->method('getIncrementId')->willReturn('000000123');
        $orderMock->method('getEntityId')->willReturn(123);
        $orderMock->method('getCreatedAt')->willReturn('2024-01-15 10:00:00');

        $key = $this->generator->generate($orderMock);

        $this->assertStringStartsWith('ERP_', $key);
        $this->assertEquals(68, strlen($key)); // ERP_ (4) + SHA256 hash (64)
    }

    public function testGenerateCreatesUniqueKeysForDifferentOrders()
    {
        $order1Mock = $this->createMock(OrderInterface::class);
        $order1Mock->method('getIncrementId')->willReturn('000000123');
        $order1Mock->method('getEntityId')->willReturn(123);
        $order1Mock->method('getCreatedAt')->willReturn('2024-01-15 10:00:00');

        $order2Mock = $this->createMock(OrderInterface::class);
        $order2Mock->method('getIncrementId')->willReturn('000000124');
        $order2Mock->method('getEntityId')->willReturn(124);
        $order2Mock->method('getCreatedAt')->willReturn('2024-01-15 10:00:01');

        $key1 = $this->generator->generate($order1Mock);
        $key2 = $this->generator->generate($order2Mock);

        $this->assertNotEquals($key1, $key2);
    }

    public function testGenerateCreatesSameKeyForSameOrder()
    {
        $orderMock = $this->createMock(OrderInterface::class);
        $orderMock->method('getIncrementId')->willReturn('000000123');
        $orderMock->method('getEntityId')->willReturn(123);
        $orderMock->method('getCreatedAt')->willReturn('2024-01-15 10:00:00');

        $key1 = $this->generator->generate($orderMock);
        $key2 = $this->generator->generate($orderMock);

        $this->assertEquals($key1, $key2);
    }

    public function testGenerateFromIncrementId()
    {
        $incrementId = '000000123';
        $timestamp = '2024-01-15 10:00:00';

        $key = $this->generator->generateFromIncrementId($incrementId, $timestamp);

        $this->assertStringStartsWith('ERP_', $key);
        $this->assertEquals(68, strlen($key));
    }

    public function testGenerateFromIncrementIdUsesCurrentTimeWhenNotProvided()
    {
        $incrementId = '000000123';

        $key1 = $this->generator->generateFromIncrementId($incrementId);
        // Small delay to ensure different timestamp
        usleep(1000);
        $key2 = $this->generator->generateFromIncrementId($incrementId);

        $this->assertStringStartsWith('ERP_', $key1);
        $this->assertStringStartsWith('ERP_', $key2);
        // Keys should be different because timestamps are different
        $this->assertNotEquals($key1, $key2);
    }
}

