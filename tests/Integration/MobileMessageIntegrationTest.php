<?php

declare(strict_types=1);

namespace MobileMessage\Tests\Integration;

use MobileMessage\DataObjects\Message;
use MobileMessage\MobileMessageClient;
use MobileMessage\Exceptions\AuthenticationException;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Mobile Message API
 * 
 * These tests require valid API credentials to run.
 * 
 * Setup:
 * 1. Copy .env.example to .env
 * 2. Add your Mobile Message API credentials
 * 3. Set TEST_PHONE_NUMBER to a number you own
 * 4. Set ENABLE_REAL_SMS_TESTS=true to test actual SMS sending
 * 
 * To run integration tests:
 * ./vendor/bin/phpunit --testsuite Integration
 */
class MobileMessageIntegrationTest extends TestCase
{
    private ?MobileMessageClient $client = null;
    private ?string $testPhoneNumber = null;
    private ?string $testSenderId = null;
    private bool $enableRealSmsTests = false;

    protected function setUp(): void
    {
        // Load .env file if it exists
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue; // Skip comments
                if (strpos($line, '=') === false) continue; // Skip invalid lines
                
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }

        $username = $_ENV['MOBILE_MESSAGE_USERNAME'] ?? getenv('MOBILE_MESSAGE_USERNAME') ?: null;
        $password = $_ENV['MOBILE_MESSAGE_PASSWORD'] ?? getenv('MOBILE_MESSAGE_PASSWORD') ?: null;
        $this->testPhoneNumber = $_ENV['TEST_PHONE_NUMBER'] ?? getenv('TEST_PHONE_NUMBER') ?: null;
        $this->testSenderId = $_ENV['TEST_SENDER_ID'] ?? getenv('TEST_SENDER_ID') ?: 'TEST';
        $this->enableRealSmsTests = filter_var(
            $_ENV['ENABLE_REAL_SMS_TESTS'] ?? getenv('ENABLE_REAL_SMS_TESTS') ?: 'false',
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$username || !$password) {
            $this->markTestSkipped('Integration tests require MOBILE_MESSAGE_USERNAME and MOBILE_MESSAGE_PASSWORD in .env file');
        }

        if ($username === 'your_username_here' || $password === 'your_password_here') {
            $this->markTestSkipped('Integration tests require real API credentials, please update your .env file');
        }

        $this->client = new MobileMessageClient($username, $password);
    }

    public function testGetBalance(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('No client available');
        }

        $balance = $this->client->getBalance();

        $this->assertIsInt($balance->getBalance());
        $this->assertIsString($balance->getPlan());
        $this->assertGreaterThanOrEqual(0, $balance->getBalance());
        
        echo "\n✅ Balance check successful: {$balance->getBalance()} credits on {$balance->getPlan()} plan\n";
    }

    public function testSendMessageWithInvalidCredentials(): void
    {
        $client = new MobileMessageClient('invalid_user', 'invalid_pass');

        $this->expectException(AuthenticationException::class);
        
        $client->sendMessage('0412345678', 'Test message', 'TestSender');
    }

    public function testSendRealMessage(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('No client available');
        }

        if (!$this->enableRealSmsTests) {
            $this->markTestSkipped('Real SMS tests disabled. Set ENABLE_REAL_SMS_TESTS=true in .env to enable');
        }

        if (!$this->testPhoneNumber) {
            $this->markTestSkipped('TEST_PHONE_NUMBER not configured in .env file');
        }

        $testMessage = 'Integration test from Mobile Message PHP SDK at ' . date('Y-m-d H:i:s');
        $customRef = 'integration-test-' . time();

        echo "\n📱 Sending real SMS to {$this->testPhoneNumber}...\n";

        $response = $this->client->sendMessage(
            $this->testPhoneNumber,
            $testMessage,
            $this->testSenderId,
            $customRef
        );

        $this->assertTrue($response->isSuccess());
        $this->assertNotEmpty($response->getMessageId());
        $this->assertEquals($this->testPhoneNumber, $response->getTo());
        $this->assertEquals($this->testSenderId, $response->getSender());
        $this->assertEquals($customRef, $response->getCustomRef());
        $this->assertGreaterThan(0, $response->getCost());

        echo "✅ SMS sent successfully! Message ID: {$response->getMessageId()}, Cost: {$response->getCost()}\n";

        // Test message status lookup
        echo "🔍 Checking message status...\n";
        
        $status = $this->client->getMessageStatus($response->getMessageId());
        
        $this->assertEquals($response->getMessageId(), $status->getMessageId());
        $this->assertEquals($this->testPhoneNumber, $status->getTo());
        $this->assertEquals($testMessage, $status->getMessage());
        $this->assertEquals($this->testSenderId, $status->getSender());
        $this->assertNotEmpty($status->getStatus());
        
        echo "✅ Status check successful: {$status->getStatus()}\n";
    }

    public function testSendBulkMessages(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('No client available');
        }

        if (!$this->enableRealSmsTests) {
            $this->markTestSkipped('Real SMS tests disabled. Set ENABLE_REAL_SMS_TESTS=true in .env to enable');
        }

        if (!$this->testPhoneNumber) {
            $this->markTestSkipped('TEST_PHONE_NUMBER not configured in .env file');
        }

        $timestamp = time();
        $messages = [
            new Message(
                $this->testPhoneNumber, 
                "Bulk test message 1 sent at " . date('H:i:s'), 
                $this->testSenderId,
                "bulk-1-{$timestamp}"
            ),
            new Message(
                $this->testPhoneNumber, 
                "Bulk test message 2 sent at " . date('H:i:s'), 
                $this->testSenderId,
                "bulk-2-{$timestamp}"
            ),
        ];

        echo "\n📱 Sending bulk SMS messages...\n";

        $responses = $this->client->sendMessages($messages);

        $this->assertCount(2, $responses);
        
        foreach ($responses as $index => $response) {
            $messageNum = $index + 1;
            $this->assertTrue($response->isSuccess(), "Message {$messageNum} should be successful");
            $this->assertNotEmpty($response->getMessageId(), "Message {$messageNum} should have ID");
            $this->assertEquals($this->testPhoneNumber, $response->getTo());
            $this->assertEquals($this->testSenderId, $response->getSender());
            
            echo "✅ Bulk message {$messageNum} sent: ID {$response->getMessageId()}, Cost: {$response->getCost()}\n";
        }
    }

    public function testSimpleApiEndpoint(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('No client available');
        }

        if (!$this->enableRealSmsTests) {
            $this->markTestSkipped('Real SMS tests disabled. Set ENABLE_REAL_SMS_TESTS=true in .env to enable');
        }

        if (!$this->testPhoneNumber) {
            $this->markTestSkipped('TEST_PHONE_NUMBER not configured in .env file');
        }

        $testMessage = 'Simple API test at ' . date('H:i:s');

        echo "\n📱 Testing simple API endpoint...\n";

        $response = $this->client->sendSimpleMessage(
            $this->testPhoneNumber,
            $testMessage,
            $this->testSenderId
        );

        $this->assertTrue($response->isSuccess());
        $this->assertNotEmpty($response->getMessageId());
        
        echo "✅ Simple API test successful! Message ID: {$response->getMessageId()}\n";
    }

    public function testApiErrorHandling(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('No client available');
        }

        // Test with invalid phone number
        echo "\n🧪 Testing error handling with invalid phone number...\n";
        
        try {
            $this->client->sendMessage('invalid-phone', 'Test', $this->testSenderId);
            $this->fail('Should have thrown an exception for invalid phone number');
        } catch (\Exception $e) {
            echo "✅ Correctly handled invalid phone number: " . $e->getMessage() . "\n";
            $this->addToAssertionCount(1);
        }

        // Test with empty message
        echo "🧪 Testing error handling with empty message...\n";
        
        try {
            $this->client->sendMessage($this->testPhoneNumber ?: '61412345678', '', $this->testSenderId);
            $this->fail('Should have thrown an exception for empty message');
        } catch (\Exception $e) {
            echo "✅ Correctly handled empty message: " . $e->getMessage() . "\n";
            $this->addToAssertionCount(1);
        }
    }
} 