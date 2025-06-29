<?php

declare(strict_types=1);

namespace MobileMessage\Tests\Unit;

use MobileMessage\DataObjects\MessageResponse;
use PHPUnit\Framework\TestCase;

class MessageResponseTest extends TestCase
{
    public function testMessageResponseConstruction(): void
    {
        $response = new MessageResponse(
            '0412345678',
            'Test message',
            'TestSender',
            'success',
            1,
            'msg123',
            'ref123'
        );

        $this->assertEquals('0412345678', $response->getTo());
        $this->assertEquals('Test message', $response->getMessage());
        $this->assertEquals('TestSender', $response->getSender());
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals(1, $response->getCost());
        $this->assertEquals('msg123', $response->getMessageId());
        $this->assertEquals('ref123', $response->getCustomRef());
    }

    public function testIsSuccess(): void
    {
        $response = new MessageResponse('0412345678', 'Test', 'Sender', 'success', 1, 'msg123');
        $this->assertTrue($response->isSuccess());

        $response = new MessageResponse('0412345678', 'Test', 'Sender', 'error', 1, 'msg123');
        $this->assertFalse($response->isSuccess());
    }

    public function testIsError(): void
    {
        $response = new MessageResponse('0412345678', 'Test', 'Sender', 'error', 1, 'msg123');
        $this->assertTrue($response->isError());

        $response = new MessageResponse('0412345678', 'Test', 'Sender', 'blocked', 1, 'msg123');
        $this->assertTrue($response->isError());

        $response = new MessageResponse('0412345678', 'Test', 'Sender', 'failed', 1, 'msg123');
        $this->assertTrue($response->isError());

        $response = new MessageResponse('0412345678', 'Test', 'Sender', 'success', 1, 'msg123');
        $this->assertFalse($response->isError());
    }

    public function testFromArray(): void
    {
        $data = [
            'to' => '0412345678',
            'message' => 'Test message',
            'sender' => 'TestSender',
            'status' => 'success',
            'cost' => 1,
            'message_id' => 'msg123',
            'custom_ref' => 'ref123',
        ];

        $response = MessageResponse::fromArray($data);

        $this->assertEquals('0412345678', $response->getTo());
        $this->assertEquals('Test message', $response->getMessage());
        $this->assertEquals('TestSender', $response->getSender());
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals(1, $response->getCost());
        $this->assertEquals('msg123', $response->getMessageId());
        $this->assertEquals('ref123', $response->getCustomRef());
    }

    public function testToArray(): void
    {
        $response = new MessageResponse(
            '0412345678',
            'Test message',
            'TestSender',
            'success',
            1,
            'msg123',
            'ref123'
        );

        $expected = [
            'to' => '0412345678',
            'message' => 'Test message',
            'sender' => 'TestSender',
            'status' => 'success',
            'cost' => 1,
            'message_id' => 'msg123',
            'custom_ref' => 'ref123',
        ];

        $this->assertEquals($expected, $response->toArray());
    }
} 