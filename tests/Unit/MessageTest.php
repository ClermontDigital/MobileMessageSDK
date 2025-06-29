<?php

declare(strict_types=1);

namespace MobileMessage\Tests\Unit;

use MobileMessage\DataObjects\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testMessageConstruction(): void
    {
        $message = new Message('0412345678', 'Test message', 'TestSender', 'ref123');

        $this->assertEquals('0412345678', $message->getTo());
        $this->assertEquals('Test message', $message->getMessage());
        $this->assertEquals('TestSender', $message->getSender());
        $this->assertEquals('ref123', $message->getCustomRef());
    }

    public function testMessageConstructionWithoutCustomRef(): void
    {
        $message = new Message('0412345678', 'Test message', 'TestSender');

        $this->assertEquals('0412345678', $message->getTo());
        $this->assertEquals('Test message', $message->getMessage());
        $this->assertEquals('TestSender', $message->getSender());
        $this->assertNull($message->getCustomRef());
    }

    public function testToArray(): void
    {
        $message = new Message('0412345678', 'Test message', 'TestSender', 'ref123');
        $array = $message->toArray();

        $expected = [
            'to' => '0412345678',
            'message' => 'Test message',
            'sender' => 'TestSender',
            'custom_ref' => 'ref123',
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithoutCustomRef(): void
    {
        $message = new Message('0412345678', 'Test message', 'TestSender');
        $array = $message->toArray();

        $expected = [
            'to' => '0412345678',
            'message' => 'Test message',
            'sender' => 'TestSender',
        ];

        $this->assertEquals($expected, $array);
    }

    public function testFromArray(): void
    {
        $data = [
            'to' => '0412345678',
            'message' => 'Test message',
            'sender' => 'TestSender',
            'custom_ref' => 'ref123',
        ];

        $message = Message::fromArray($data);

        $this->assertEquals('0412345678', $message->getTo());
        $this->assertEquals('Test message', $message->getMessage());
        $this->assertEquals('TestSender', $message->getSender());
        $this->assertEquals('ref123', $message->getCustomRef());
    }

    public function testFromArrayWithMissingFields(): void
    {
        $data = [
            'to' => '0412345678',
        ];

        $message = Message::fromArray($data);

        $this->assertEquals('0412345678', $message->getTo());
        $this->assertEquals('', $message->getMessage());
        $this->assertEquals('', $message->getSender());
        $this->assertNull($message->getCustomRef());
    }
} 