<?php

declare(strict_types=1);

namespace MobileMessage\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MobileMessage\DataObjects\Message;
use MobileMessage\Exceptions\ValidationException;
use MobileMessage\MobileMessageClient;
use PHPUnit\Framework\TestCase;

class MobileMessageClientTest extends TestCase
{
    private function createMockClient(array $responses): MobileMessageClient
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new MobileMessageClient('test_user', 'test_pass');
        
        // Use reflection to inject the mock HTTP client
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        return $client;
    }

    public function testSendMessage(): void
    {
        $responseData = [
            'status' => 'complete',
            'total_cost' => 1,
            'results' => [
                [
                    'to' => '0412345678',
                    'message' => 'Test message',
                    'sender' => 'TestSender',
                    'status' => 'success',
                    'cost' => 1,
                    'message_id' => 'msg123',
                    'custom_ref' => 'ref123',
                ]
            ]
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData))
        ]);

        $response = $client->sendMessage('0412345678', 'Test message', 'TestSender', 'ref123');

        $this->assertEquals('0412345678', $response->getTo());
        $this->assertEquals('Test message', $response->getMessage());
        $this->assertEquals('TestSender', $response->getSender());
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals(1, $response->getCost());
        $this->assertEquals('msg123', $response->getMessageId());
        $this->assertEquals('ref123', $response->getCustomRef());
    }

    public function testValidateMessageThrowsExceptionForEmptyMessage(): void
    {
        $client = new MobileMessageClient('test_user', 'test_pass');
        $message = new Message('0412345678', '', 'TestSender');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Message content cannot be empty');

        $client->validateMessage($message);
    }

    public function testValidateMessageThrowsExceptionForLongMessage(): void
    {
        $client = new MobileMessageClient('test_user', 'test_pass');
        $longMessage = str_repeat('a', 766); // Over the 765 character limit
        $message = new Message('0412345678', $longMessage, 'TestSender');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Message exceeds maximum length of 765 characters');

        $client->validateMessage($message);
    }

    public function testValidateMessageThrowsExceptionForEmptyRecipient(): void
    {
        $client = new MobileMessageClient('test_user', 'test_pass');
        $message = new Message('', 'Test message', 'TestSender');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Recipient phone number is required');

        $client->validateMessage($message);
    }

    public function testValidateMessageThrowsExceptionForEmptySender(): void
    {
        $client = new MobileMessageClient('test_user', 'test_pass');
        $message = new Message('0412345678', 'Test message', '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Sender ID is required');

        $client->validateMessage($message);
    }

    public function testSendMessagesThrowsExceptionForEmptyArray(): void
    {
        $client = new MobileMessageClient('test_user', 'test_pass');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Messages array cannot be empty');

        $client->sendMessages([]);
    }

    public function testSendMessagesThrowsExceptionForTooManyMessages(): void
    {
        $client = new MobileMessageClient('test_user', 'test_pass');
        $messages = [];

        // Create 101 messages (over the limit)
        for ($i = 0; $i < 101; $i++) {
            $messages[] = new Message('0412345678', 'Test', 'Sender');
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Maximum 100 messages allowed per request');

        $client->sendMessages($messages);
    }
} 