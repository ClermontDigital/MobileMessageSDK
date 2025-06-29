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
 * Set MOBILE_MESSAGE_USERNAME and MOBILE_MESSAGE_PASSWORD environment variables.
 * 
 * To run integration tests:
 * MOBILE_MESSAGE_USERNAME=your_user MOBILE_MESSAGE_PASSWORD=your_pass ./vendor/bin/phpunit --testsuite Integration
 */
class MobileMessageIntegrationTest extends TestCase
{
    private ?MobileMessageClient $client = null;

    protected function setUp(): void
    {
        $username = $_ENV['MOBILE_MESSAGE_USERNAME'] ?? null;
        $password = $_ENV['MOBILE_MESSAGE_PASSWORD'] ?? null;

        if (!$username || !$password) {
            $this->markTestSkipped('Integration tests require MOBILE_MESSAGE_USERNAME and MOBILE_MESSAGE_PASSWORD environment variables');
        }

        if ($username === 'test_user' || $password === 'test_pass') {
            $this->markTestSkipped('Integration tests require real API credentials, not test values');
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
    }

    public function testSendMessageWithInvalidCredentials(): void
    {
        $client = new MobileMessageClient('invalid_user', 'invalid_pass');

        $this->expectException(AuthenticationException::class);
        
        $client->sendMessage('0412345678', 'Test message', 'TestSender');
    }

    /**
     * This test is commented out to prevent accidental SMS sending during automated tests.
     * Uncomment and modify the phone number to test actual message sending.
     */
    /*
    public function testSendRealMessage(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('No client available');
        }

        // IMPORTANT: Replace with a real phone number you own for testing
        $testPhoneNumber = '61412345678'; // Your test phone number here
        $testSender = 'TEST'; // Your approved sender ID here

        $response = $this->client->sendMessage(
            $testPhoneNumber,
            'This is a test message from the Mobile Message PHP SDK',
            $testSender,
            'integration-test-' . time()
        );

        $this->assertTrue($response->isSuccess());
        $this->assertNotEmpty($response->getMessageId());
        $this->assertEquals($testPhoneNumber, $response->getTo());
        $this->assertEquals($testSender, $response->getSender());
    }
    */

    /**
     * Test bulk message sending (commented out for safety)
     */
    /*
    public function testSendBulkMessages(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('No client available');
        }

        $messages = [
            new Message('61412345678', 'Bulk message 1', 'TEST', 'bulk-1-' . time()),
            new Message('61412345679', 'Bulk message 2', 'TEST', 'bulk-2-' . time()),
        ];

        $responses = $this->client->sendMessages($messages);

        $this->assertCount(2, $responses);
        foreach ($responses as $response) {
            $this->assertTrue($response->isSuccess());
            $this->assertNotEmpty($response->getMessageId());
        }
    }
    */
} 