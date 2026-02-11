<?php

declare(strict_types=1);

namespace MobileMessage\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MobileMessage\DataObjects\Message;
use MobileMessage\Exceptions\AuthenticationException;
use MobileMessage\Exceptions\MobileMessageException;
use MobileMessage\Exceptions\RateLimitException;
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

    // ---------------------------------------------------------------
    // Existing tests
    // ---------------------------------------------------------------

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

    // ---------------------------------------------------------------
    // New: Method coverage tests
    // ---------------------------------------------------------------

    public function testGetBalance(): void
    {
        $responseData = [
            'credit_balance' => 250,
            'plan' => 'Premium',
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData))
        ]);

        $balance = $client->getBalance();

        $this->assertEquals(250, $balance->getBalance());
        $this->assertEquals('Premium', $balance->getPlan());
        $this->assertTrue($balance->hasCredits());
    }

    public function testGetMessage(): void
    {
        $responseData = [
            'results' => [
                [
                    'message_id' => 'msg123',
                    'to' => '0412345678',
                    'message' => 'Test',
                    'sender' => 'TestSender',
                    'status' => 'delivered',
                    'cost' => 1,
                    'sent_at' => '2024-01-01 10:00:00',
                    'delivered_at' => '2024-01-01 10:01:30',
                ]
            ]
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData))
        ]);

        $status = $client->getMessage('msg123');

        $this->assertEquals('msg123', $status->getMessageId());
        $this->assertEquals('delivered', $status->getStatus());
        $this->assertTrue($status->isDelivered());
        $this->assertEquals('2024-01-01 10:00:00', $status->getSentAt());
        $this->assertEquals('2024-01-01 10:01:30', $status->getDeliveredAt());
    }

    public function testSendSimple(): void
    {
        $responseData = [
            'results' => [
                [
                    'to' => '0412345678',
                    'message' => 'Simple test',
                    'sender' => 'TestSender',
                    'status' => 'success',
                    'cost' => 1,
                    'message_id' => 'simple123',
                ]
            ]
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData))
        ]);

        @$response = $client->sendSimple('0412345678', 'Simple test', 'TestSender');

        $this->assertEquals('simple123', $response->getMessageId());
        $this->assertTrue($response->isSuccess());
    }

    // ---------------------------------------------------------------
    // New: HTTP error handling tests
    // ---------------------------------------------------------------

    public function testSendMessageHandles401(): void
    {
        $client = $this->createMockClient([
            RequestException::create(
                new Request('POST', 'v1/messages'),
                new Response(401, [], json_encode(['message' => 'Unauthorized']))
            )
        ]);

        $this->expectException(AuthenticationException::class);
        $client->sendMessage('0412345678', 'Test', 'Sender');
    }

    public function testSendMessageHandles429(): void
    {
        $client = $this->createMockClient([
            RequestException::create(
                new Request('POST', 'v1/messages'),
                new Response(429, [], json_encode(['message' => 'Rate limited']))
            )
        ]);

        $this->expectException(RateLimitException::class);
        $client->sendMessage('0412345678', 'Test', 'Sender');
    }

    public function testSendMessageHandles400(): void
    {
        $client = $this->createMockClient([
            RequestException::create(
                new Request('POST', 'v1/messages'),
                new Response(400, [], json_encode(['message' => 'Bad phone number']))
            )
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bad phone number');
        $client->sendMessage('0412345678', 'Test', 'Sender');
    }

    public function testSendMessageHandles500(): void
    {
        $client = $this->createMockClient([
            RequestException::create(
                new Request('POST', 'v1/messages'),
                new Response(500, [], 'Internal Server Error')
            )
        ]);

        $this->expectException(MobileMessageException::class);
        $client->sendMessage('0412345678', 'Test', 'Sender');
    }

    public function testHandlesNetworkFailure(): void
    {
        $client = $this->createMockClient([
            new ConnectException(
                'Connection timed out',
                new Request('POST', 'v1/messages')
            )
        ]);

        $this->expectException(MobileMessageException::class);
        $this->expectExceptionMessage('Network error:');
        $client->sendMessage('0412345678', 'Test', 'Sender');
    }

    // ---------------------------------------------------------------
    // New: Empty/invalid response tests
    // ---------------------------------------------------------------

    public function testSendMessageThrowsOnEmptyResults(): void
    {
        $responseData = [
            'status' => 'complete',
            'total_cost' => 0,
            'results' => []
        ];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData))
        ]);

        $this->expectException(MobileMessageException::class);
        $this->expectExceptionMessage('API returned no results');
        $client->sendMessage('0412345678', 'Test', 'Sender');
    }

    public function testGetMessageThrowsOnEmptyResults(): void
    {
        $responseData = ['results' => []];

        $client = $this->createMockClient([
            new Response(200, [], json_encode($responseData))
        ]);

        $this->expectException(MobileMessageException::class);
        $this->expectExceptionMessage('Message not found');
        $client->getMessage('nonexistent-id');
    }

    public function testSendSimpleThrowsOnEmptyResponse(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode([]))
        ]);

        $this->expectException(MobileMessageException::class);
        $this->expectExceptionMessage('API returned no results for the simple send request');
        @$client->sendSimple('0412345678', 'Test', 'Sender');
    }

    public function testMakeRequestThrowsOnInvalidJson(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], 'not valid json{{{')
        ]);

        $this->expectException(MobileMessageException::class);
        $this->expectExceptionMessage('invalid JSON');
        $client->getBalance();
    }

    // ---------------------------------------------------------------
    // New: Input validation tests
    // ---------------------------------------------------------------

    public function testConstructorRejectsEmptyUsername(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('API username cannot be empty');
        new MobileMessageClient('', 'password');
    }

    public function testConstructorRejectsEmptyPassword(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('API password cannot be empty');
        new MobileMessageClient('username', '');
    }

    public function testGetMessageRejectsEmptyId(): void
    {
        $client = new MobileMessageClient('test_user', 'test_pass');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Message ID cannot be empty');
        $client->getMessage('');
    }

    public function testSendMessageValidatesBeforeSending(): void
    {
        $client = new MobileMessageClient('test_user', 'test_pass');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Message content cannot be empty');
        $client->sendMessage('0412345678', '', 'Sender');
    }
}
