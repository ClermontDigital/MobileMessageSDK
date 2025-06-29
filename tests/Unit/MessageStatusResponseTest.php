<?php

declare(strict_types=1);

namespace MobileMessage\Tests\Unit;

use MobileMessage\DataObjects\MessageStatusResponse;
use PHPUnit\Framework\TestCase;

class MessageStatusResponseTest extends TestCase
{
    public function testMessageStatusResponseConstruction(): void
    {
        $response = new MessageStatusResponse(
            'msg123',
            '0412345678',
            'Test message',
            'TestSender',
            'delivered',
            1,
            'ref123',
            '2024-01-01 10:00:00',
            '2024-01-01 10:01:30'
        );

        $this->assertEquals('msg123', $response->getMessageId());
        $this->assertEquals('0412345678', $response->getTo());
        $this->assertEquals('Test message', $response->getMessage());
        $this->assertEquals('TestSender', $response->getSender());
        $this->assertEquals('delivered', $response->getStatus());
        $this->assertEquals(1, $response->getCost());
        $this->assertEquals('ref123', $response->getCustomRef());
        $this->assertEquals('2024-01-01 10:00:00', $response->getSentAt());
        $this->assertEquals('2024-01-01 10:01:30', $response->getDeliveredAt());
    }

    public function testIsDelivered(): void
    {
        $deliveredResponse = new MessageStatusResponse('msg1', '0412345678', 'Test', 'Sender', 'delivered', 1);
        $this->assertTrue($deliveredResponse->isDelivered());

        $sentResponse = new MessageStatusResponse('msg2', '0412345678', 'Test', 'Sender', 'sent', 1);
        $this->assertFalse($sentResponse->isDelivered());
    }

    public function testIsPending(): void
    {
        $sentResponse = new MessageStatusResponse('msg1', '0412345678', 'Test', 'Sender', 'sent', 1);
        $this->assertTrue($sentResponse->isPending());

        $pendingResponse = new MessageStatusResponse('msg2', '0412345678', 'Test', 'Sender', 'pending', 1);
        $this->assertTrue($pendingResponse->isPending());

        $deliveredResponse = new MessageStatusResponse('msg3', '0412345678', 'Test', 'Sender', 'delivered', 1);
        $this->assertFalse($deliveredResponse->isPending());
    }

    public function testIsFailed(): void
    {
        $failedResponse = new MessageStatusResponse('msg1', '0412345678', 'Test', 'Sender', 'failed', 1);
        $this->assertTrue($failedResponse->isFailed());

        $errorResponse = new MessageStatusResponse('msg2', '0412345678', 'Test', 'Sender', 'error', 1);
        $this->assertTrue($errorResponse->isFailed());

        $blockedResponse = new MessageStatusResponse('msg3', '0412345678', 'Test', 'Sender', 'blocked', 1);
        $this->assertTrue($blockedResponse->isFailed());

        $deliveredResponse = new MessageStatusResponse('msg4', '0412345678', 'Test', 'Sender', 'delivered', 1);
        $this->assertFalse($deliveredResponse->isFailed());
    }

    public function testFromArray(): void
    {
        $data = [
            'message_id' => 'msg456',
            'to' => '0412345679',
            'message' => 'Another test message',
            'sender' => 'AnotherSender',
            'status' => 'sent',
            'cost' => 2,
            'custom_ref' => 'ref456',
            'sent_at' => '2024-01-02 14:30:00',
            'delivered_at' => null,
        ];

        $response = MessageStatusResponse::fromArray($data);

        $this->assertEquals('msg456', $response->getMessageId());
        $this->assertEquals('0412345679', $response->getTo());
        $this->assertEquals('Another test message', $response->getMessage());
        $this->assertEquals('AnotherSender', $response->getSender());
        $this->assertEquals('sent', $response->getStatus());
        $this->assertEquals(2, $response->getCost());
        $this->assertEquals('ref456', $response->getCustomRef());
        $this->assertEquals('2024-01-02 14:30:00', $response->getSentAt());
        $this->assertNull($response->getDeliveredAt());
    }

    public function testFromArrayWithMissingFields(): void
    {
        $data = [
            'message_id' => 'msg789',
        ];

        $response = MessageStatusResponse::fromArray($data);

        $this->assertEquals('msg789', $response->getMessageId());
        $this->assertEquals('', $response->getTo());
        $this->assertEquals('', $response->getMessage());
        $this->assertEquals('', $response->getSender());
        $this->assertEquals('', $response->getStatus());
        $this->assertEquals(0, $response->getCost());
        $this->assertNull($response->getCustomRef());
        $this->assertNull($response->getSentAt());
        $this->assertNull($response->getDeliveredAt());
    }

    public function testToArray(): void
    {
        $response = new MessageStatusResponse(
            'msg999',
            '0412345680',
            'Final test message',
            'FinalSender',
            'delivered',
            3,
            'ref999',
            '2024-01-03 09:15:00',
            '2024-01-03 09:16:45'
        );

        $expected = [
            'message_id' => 'msg999',
            'to' => '0412345680',
            'message' => 'Final test message',
            'sender' => 'FinalSender',
            'status' => 'delivered',
            'cost' => 3,
            'custom_ref' => 'ref999',
            'sent_at' => '2024-01-03 09:15:00',
            'delivered_at' => '2024-01-03 09:16:45',
        ];

        $this->assertEquals($expected, $response->toArray());
    }

    public function testToArrayWithoutOptionalFields(): void
    {
        $response = new MessageStatusResponse(
            'msg111',
            '0412345681',
            'Simple message',
            'SimpleSender',
            'sent',
            1
        );

        $expected = [
            'message_id' => 'msg111',
            'to' => '0412345681',
            'message' => 'Simple message',
            'sender' => 'SimpleSender',
            'status' => 'sent',
            'cost' => 1,
        ];

        $this->assertEquals($expected, $response->toArray());
    }
} 