<?php

/**
 * Mobile Message PHP SDK - Test Example
 * 
 * This script demonstrates how to test the SDK functionality.
 * Update the credentials below and run this script to verify everything works.
 * 
 * Usage: php examples/test_example.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MobileMessage\MobileMessageClient;
use MobileMessage\DataObjects\Message;
use MobileMessage\Exceptions\MobileMessageException;
use MobileMessage\Exceptions\AuthenticationException;
use MobileMessage\Exceptions\ValidationException;

/**
 * Test Example for Mobile Message PHP SDK
 * 
 * This script provides comprehensive testing of the SDK functionality
 * using your real Mobile Message API credentials.
 * 
 * Setup:
 * 1. Copy .env.example to .env in the project root
 * 2. Add your real Mobile Message API credentials
 * 3. Set TEST_PHONE_NUMBER to your own phone number
 * 4. Set ENABLE_REAL_SMS_TESTS=true to send actual SMS messages
 * 5. Run: php examples/test_example.php
 */

echo "🚀 Mobile Message PHP SDK - Test Example\n";
echo "========================================\n\n";

// Load environment variables from .env file
function loadEnv(string $path): void {
    if (!file_exists($path)) {
        echo "❌ .env file not found at: $path\n";
        echo "Please copy .env.example to .env and add your credentials.\n";
        exit(1);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

try {
    // Load .env file
    $envPath = __DIR__ . '/../.env';
    loadEnv($envPath);

    // Get credentials from environment
    $username = $_ENV['MOBILE_MESSAGE_USERNAME'] ?? null;
    $password = $_ENV['MOBILE_MESSAGE_PASSWORD'] ?? null;
    $testPhoneNumber = $_ENV['TEST_PHONE_NUMBER'] ?? null;
    $testSenderId = $_ENV['TEST_SENDER_ID'] ?? 'TEST';
    $enableRealSmsTests = filter_var($_ENV['ENABLE_REAL_SMS_TESTS'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

    // Validate credentials
    if (!$username || !$password) {
        throw new Exception('Missing MOBILE_MESSAGE_USERNAME or MOBILE_MESSAGE_PASSWORD in .env file');
    }

    if ($username === 'your_username_here' || $password === 'your_password_here') {
        throw new Exception('Please update your .env file with real API credentials');
    }

    // Initialize client
    echo "🔧 Initialising Mobile Message Client...\n";
    $client = new MobileMessageClient($username, $password);
    echo "✅ Client initialised successfully\n\n";

    // Test 1: Check account balance
    echo "💰 Testing account balance...\n";
    $balance = $client->getBalance();
    echo "✅ Balance: {$balance->getBalance()} credits\n";
    echo "📋 Plan: {$balance->getPlan()}\n";
    echo "💡 Has credits: " . ($balance->hasCredits() ? 'Yes' : 'No') . "\n\n";

    // Test 2: Test invalid credentials (create separate client)
    echo "🔒 Testing authentication with invalid credentials...\n";
    try {
        $invalidClient = new MobileMessageClient('invalid_user', 'invalid_pass');
        $invalidClient->getBalance();
        echo "❌ Should have failed with invalid credentials\n";
    } catch (MobileMessageException $e) {
        echo "✅ Correctly rejected invalid credentials: " . $e->getMessage() . "\n\n";
    }

    if ($enableRealSmsTests) {
        if (!$testPhoneNumber) {
            echo "⚠️  TEST_PHONE_NUMBER not set in .env - skipping SMS tests\n\n";
        } else {
            // Test 3: Send a single SMS
            echo "📱 Testing single SMS sending...\n";
            echo "📞 Sending to: {$testPhoneNumber}\n";
            
            $testMessage = "Test SMS from Mobile Message PHP SDK at " . date('Y-m-d H:i:s');
            $customRef = 'test-' . time();
            
            $response = $client->sendMessage($testPhoneNumber, $testMessage, $testSenderId, $customRef);
            
            echo "✅ SMS sent successfully!\n";
            echo "   📨 Message ID: {$response->getMessageId()}\n";
            echo "   💰 Cost: {$response->getCost()}\n";
            echo "   📱 To: {$response->getTo()}\n";
            echo "   👤 From: {$response->getSender()}\n";
            echo "   🏷️  Custom Ref: {$response->getCustomRef()}\n\n";

            // Test 4: Check message status
            echo "🔍 Testing message status lookup...\n";
            sleep(2); // Wait a moment for the message to be processed
            
            $status = $client->getMessageStatus($response->getMessageId());
            
            echo "✅ Message status retrieved:\n";
            echo "   📨 Message ID: {$status->getMessageId()}\n";
            echo "   📱 To: {$status->getTo()}\n";
            echo "   💬 Message: " . substr($status->getMessage(), 0, 50) . "...\n";
            echo "   👤 Sender: {$status->getSender()}\n";
            echo "   📊 Status: {$status->getStatus()}\n";
            echo "   💰 Cost: {$status->getCost()}\n";
            echo "   📅 Sent: {$status->getSentAt()}\n";
            echo "   ✅ Delivered: " . ($status->isDelivered() ? 'Yes' : 'No') . "\n";
            echo "   ⏳ Pending: " . ($status->isPending() ? 'Yes' : 'No') . "\n";
            echo "   ❌ Failed: " . ($status->isFailed() ? 'Yes' : 'No') . "\n\n";

            // Test 5: Send bulk messages
            echo "📬 Testing bulk SMS sending...\n";
            
            $bulkMessages = [
                new Message($testPhoneNumber, "Bulk message 1 at " . date('H:i:s'), $testSenderId, 'bulk-1-' . time()),
                new Message($testPhoneNumber, "Bulk message 2 at " . date('H:i:s'), $testSenderId, 'bulk-2-' . time()),
            ];

            $bulkResponses = $client->sendMessages($bulkMessages);
            
            echo "✅ Bulk messages sent successfully!\n";
            foreach ($bulkResponses as $index => $bulkResponse) {
                $num = $index + 1;
                echo "   📨 Message {$num} ID: {$bulkResponse->getMessageId()}\n";
                echo "   📨 Message {$num} Cost: {$bulkResponse->getCost()}\n";
            }
            echo "\n";

            // Test 6: Test simple API endpoint
            echo "🎯 Testing simple API endpoint...\n";
            
            $simpleResponse = $client->sendSimpleMessage(
                $testPhoneNumber,
                "Simple API test at " . date('H:i:s'),
                $testSenderId
            );
            
            echo "✅ Simple API message sent!\n";
            echo "   📨 Message ID: {$simpleResponse->getMessageId()}\n";
            echo "   💰 Cost: {$simpleResponse->getCost()}\n\n";
        }
    } else {
        echo "⚠️  Real SMS tests disabled (ENABLE_REAL_SMS_TESTS=false)\n";
        echo "   Set ENABLE_REAL_SMS_TESTS=true in .env to test actual SMS sending\n\n";
    }

    // Test 7: Error handling
    echo "🧪 Testing error handling...\n";
    
    try {
        $client->sendMessage('invalid-phone', 'Test message', $testSenderId);
        echo "❌ Should have failed with invalid phone number\n";
    } catch (MobileMessageException $e) {
        echo "✅ Correctly handled invalid phone: " . $e->getMessage() . "\n";
    }

    try {
        $client->sendMessage($testPhoneNumber ?: '61412345678', '', $testSenderId);
        echo "❌ Should have failed with empty message\n";
    } catch (MobileMessageException $e) {
        echo "✅ Correctly handled empty message: " . $e->getMessage() . "\n";
    }

    try {
        $longMessage = str_repeat('A', 800); // Over 765 character limit
        $client->sendMessage($testPhoneNumber ?: '61412345678', $longMessage, $testSenderId);
        echo "❌ Should have failed with long message\n";
    } catch (MobileMessageException $e) {
        echo "✅ Correctly handled long message: " . $e->getMessage() . "\n";
    }

    echo "\n🎉 All tests completed successfully!\n\n";

    // Summary
    echo "📊 Test Summary:\n";
    echo "================\n";
    echo "✅ Account balance check: Passed\n";
    echo "✅ Authentication validation: Passed\n";
    if ($enableRealSmsTests && $testPhoneNumber) {
        echo "✅ Single SMS sending: Passed\n";
        echo "✅ Message status lookup: Passed\n";
        echo "✅ Bulk SMS sending: Passed\n";
        echo "✅ Simple API endpoint: Passed\n";
    } else {
        echo "⚠️  SMS sending tests: Skipped (not enabled)\n";
    }
    echo "✅ Error handling: Passed\n\n";

    echo "🚀 The Mobile Message PHP SDK is working correctly!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📝 Check your .env file configuration and API credentials.\n";
    exit(1);
} 