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

// ===== CONFIGURATION =====
// Update these with your actual credentials for testing
$TEST_USERNAME = 'your_test_username';
$TEST_PASSWORD = 'your_test_password';
$TEST_PHONE = '0412345678';  // Update with your test phone number
$TEST_SENDER = 'TEST';       // Update with your approved sender ID

// Set to true to actually send SMS (will cost credits)
$ACTUALLY_SEND_SMS = false;

echo "Mobile Message PHP SDK - Test Script\n";
echo "===================================\n\n";

// Test 1: Client instantiation
echo "Test 1: Client Instantiation\n";
echo "-----------------------------\n";
try {
    $client = new MobileMessageClient($TEST_USERNAME, $TEST_PASSWORD);
    echo "✅ Client created successfully\n\n";
} catch (Exception $e) {
    echo "❌ Failed to create client: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Message validation
echo "Test 2: Message Validation\n";
echo "--------------------------\n";
try {
    $validMessage = new Message($TEST_PHONE, 'Test message', $TEST_SENDER);
    $client->validateMessage($validMessage);
    echo "✅ Valid message passed validation\n";
    
    // Test invalid message (empty content)
    $invalidMessage = new Message($TEST_PHONE, '', $TEST_SENDER);
    $client->validateMessage($invalidMessage);
    echo "❌ Invalid message should have failed validation\n";
} catch (ValidationException $e) {
    echo "✅ Invalid message correctly rejected: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Authentication check
echo "Test 3: Authentication Check\n";
echo "----------------------------\n";
try {
    $balance = $client->getBalance();
    echo "✅ Authentication successful\n";
    echo "   Balance: {$balance->getBalance()} credits\n";
    echo "   Plan: {$balance->getPlan()}\n";
} catch (AuthenticationException $e) {
    echo "❌ Authentication failed: " . $e->getMessage() . "\n";
    echo "   Please check your username and password\n";
} catch (Exception $e) {
    echo "❌ Balance check failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Message object creation and serialisation
echo "Test 4: Message Object Tests\n";
echo "----------------------------\n";
try {
    $message = new Message($TEST_PHONE, 'Test message', $TEST_SENDER, 'test-ref');
    echo "✅ Message object created\n";
    
    $array = $message->toArray();
    echo "✅ Message serialised to array\n";
    
    $recreated = Message::fromArray($array);
    echo "✅ Message recreated from array\n";
    
    if ($recreated->getTo() === $message->getTo() && 
        $recreated->getMessage() === $message->getMessage() &&
        $recreated->getSender() === $message->getSender() &&
        $recreated->getCustomRef() === $message->getCustomRef()) {
        echo "✅ Message data integrity verified\n";
    } else {
        echo "❌ Message data integrity check failed\n";
    }
} catch (Exception $e) {
    echo "❌ Message object test failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Bulk message validation
echo "Test 5: Bulk Message Validation\n";
echo "-------------------------------\n";
try {
    $messages = [
        new Message($TEST_PHONE, 'Message 1', $TEST_SENDER, 'test-1'),
        new Message($TEST_PHONE, 'Message 2', $TEST_SENDER, 'test-2'),
    ];
    
    foreach ($messages as $msg) {
        $client->validateMessage($msg);
    }
    echo "✅ Bulk message validation passed\n";
    
    // Test empty array
    $client->sendMessages([]);
    echo "❌ Empty message array should have failed\n";
} catch (ValidationException $e) {
    echo "✅ Empty message array correctly rejected: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Error handling
echo "Test 6: Error Handling\n";
echo "----------------------\n";
try {
    // Test with invalid credentials
    $badClient = new MobileMessageClient('invalid_user', 'invalid_pass');
    $badClient->getBalance();
    echo "❌ Bad credentials should have failed\n";
} catch (AuthenticationException $e) {
    echo "✅ Bad credentials correctly rejected: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Unexpected error type: " . get_class($e) . " - " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Actual SMS sending (optional)
if ($ACTUALLY_SEND_SMS) {
    echo "Test 7: Actual SMS Sending\n";
    echo "--------------------------\n";
    
    if ($TEST_USERNAME === 'your_test_username' || $TEST_PHONE === '0412345678') {
        echo "⚠️  Skipping SMS test - please update TEST_USERNAME and TEST_PHONE\n";
    } else {
        try {
            $response = $client->sendMessage(
                $TEST_PHONE,
                'This is a test SMS from the Mobile Message PHP SDK - ' . date('Y-m-d H:i:s'),
                $TEST_SENDER,
                'test-' . time()
            );
            
            if ($response->isSuccess()) {
                echo "✅ SMS sent successfully!\n";
                echo "   Message ID: {$response->getMessageId()}\n";
                echo "   Cost: {$response->getCost()} credits\n";
                echo "   Status: {$response->getStatus()}\n";
                
                // Test message status lookup
                echo "\nTest 7b: Message Status Lookup\n";
                echo "------------------------------\n";
                sleep(2); // Wait a moment
                
                $status = $client->getMessage($response->getMessageId());
                echo "✅ Message status retrieved\n";
                echo "   Status: {$status->getStatus()}\n";
                echo "   Delivered: " . ($status->isDelivered() ? 'Yes' : 'No') . "\n";
                
            } else {
                echo "❌ SMS failed to send\n";
                echo "   Status: {$response->getStatus()}\n";
            }
        } catch (Exception $e) {
            echo "❌ SMS sending failed: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "Test 7: SMS Sending (SKIPPED)\n";
    echo "-----------------------------\n";
    echo "⚠️  Set ACTUALLY_SEND_SMS = true to test real SMS sending\n";
    echo "   (This will use credits from your account)\n";
}
echo "\n";

// Summary
echo "Test Summary\n";
echo "============\n";
echo "All basic functionality tests completed.\n";

if ($TEST_USERNAME === 'your_test_username') {
    echo "\n⚠️  To test with real API:\n";
    echo "   1. Update TEST_USERNAME and TEST_PASSWORD with your credentials\n";
    echo "   2. Update TEST_PHONE with your phone number\n";
    echo "   3. Update TEST_SENDER with your approved sender ID\n";
    echo "   4. Set ACTUALLY_SEND_SMS = true to test real SMS sending\n";
    echo "   5. Run: php examples/test_example.php\n";
}

echo "\nFor more examples, check:\n";
echo "- examples/basic_example.php\n";
echo "- examples/bulk_example.php\n";
echo "- examples/laravel_example.php\n";
echo "- examples/codeigniter_example.php\n";

echo "\nTo run the full test suite:\n";
echo "composer test\n";

echo "\nTest script completed!\n"; 