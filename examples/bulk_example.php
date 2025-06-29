<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MobileMessage\MobileMessageClient;
use MobileMessage\DataObjects\Message;
use MobileMessage\Exceptions\MobileMessageException;

/**
 * Bulk Example - Sending Multiple SMS Messages
 * 
 * This example shows how to send multiple SMS messages efficiently using the Mobile Message PHP SDK.
 * 
 * Setup:
 * 1. Copy .env.example to .env in the project root
 * 2. Add your Mobile Message API credentials
 * 3. Set ENABLE_REAL_SMS_TESTS=true to send actual messages
 * 4. Run: php examples/bulk_example.php
 */

// Load environment variables from .env file
function loadEnv(string $path): void {
    if (!file_exists($path)) {
        echo "❌ .env file not found. Please copy .env.example to .env and add your credentials.\n";
        exit(1);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

try {
    echo "📬 Mobile Message PHP SDK - Bulk Example\n";
    echo "========================================\n\n";

    // Load credentials from .env
    loadEnv(__DIR__ . '/../.env');
    
    $username = $_ENV['API_USERNAME'] ?? null;
    $password = $_ENV['API_PASSWORD'] ?? null;
    $testPhone = $_ENV['TEST_PHONE_NUMBER'] ?? '0400322583';
    $senderId = $_ENV['SENDER_PHONE_NUMBER'] ?? null;
    $enableBulkSmsTests = filter_var($_ENV['ENABLE_BULK_SMS_TESTS'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

    if (!$username || !$password || $username === 'your_api_username_here') {
        throw new Exception('Please configure your API credentials in the .env file');
    }

    if (!$senderId) {
        throw new Exception('Please configure your SENDER_PHONE_NUMBER in the .env file');
    }

    // Initialise the client
    echo "🔧 Initialising client...\n";
    $client = new MobileMessageClient($username, $password);

    // Check account balance
    echo "💰 Checking account balance...\n";
    $balance = $client->getBalance();
    echo "   Balance: {$balance->getBalance()} credits\n";
    echo "   Plan: {$balance->getPlan()}\n\n";

    // Check if real SMS is enabled
    $enableRealSms = filter_var($_ENV['ENABLE_REAL_SMS_TESTS'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    
    if (!$enableRealSms) {
        echo "⚠️  Real SMS sending is disabled.\n";
        echo "   Set ENABLE_REAL_SMS_TESTS=true in .env to send actual SMS messages.\n";
        echo "   This example will only validate your setup without sending messages.\n\n";
    } elseif (!$enableBulkSmsTests) {
        echo "⚠️  Bulk SMS testing is disabled.\n";
        echo "   Set ENABLE_BULK_SMS_TESTS=true in .env to send bulk SMS messages.\n";
        echo "   This will send multiple SMS messages and use more credits.\n\n";
        
        // Show what would be sent
        echo "📝 Messages that would be sent:\n";
        $timestamp = time();
        $sampleMessages = [
            new Message($testPhone, "Welcome to our service! Your account is now active.", $senderId, "welcome-{$timestamp}"),
            new Message($testPhone, "Reminder: Your appointment is tomorrow at 2 PM.", $senderId, "reminder-{$timestamp}"),
            new Message($testPhone, "Thank you for your purchase! Order #12345 is confirmed.", $senderId, "order-{$timestamp}"),
            new Message($testPhone, "Your verification code is: 123456", $senderId, "verify-{$timestamp}"),
        ];
        
        foreach ($sampleMessages as $index => $message) {
            $num = $index + 1;
            echo "   {$num}. To: {$message->getTo()}\n";
            echo "      Message: {$message->getMessage()}\n";
            echo "      From: {$message->getSender()}\n";
            echo "      Ref: {$message->getCustomRef()}\n\n";
        }
        
        echo "✅ Bulk example setup validated!\n";
        exit(0);
    }

    if (!$enableBulkSmsTests) {
        echo "⚠️  Bulk SMS testing is disabled.\n";
        echo "   Set ENABLE_BULK_SMS_TESTS=true in .env to send bulk SMS messages.\n";
        echo "   This will send multiple SMS messages and use more credits.\n\n";
        echo "✅ Bulk example validation completed!\n";
        exit(0);
    }

    // Create multiple messages
    echo "📝 Preparing bulk messages...\n";
    $timestamp = time();
    
    $messages = [
        new Message(
            $testPhone,
            "Bulk message 1: Welcome to our service! Sent at " . date('H:i:s'),
            $senderId,
            "bulk-welcome-{$timestamp}"
        ),
        new Message(
            $testPhone,
            "Bulk message 2: Your account is now active. Sent at " . date('H:i:s'),
            $senderId,
            "bulk-active-{$timestamp}"
        ),
        new Message(
            $testPhone,
            "Bulk message 3: Thank you for joining us! Sent at " . date('H:i:s'),
            $senderId,
            "bulk-thanks-{$timestamp}"
        ),
        new Message(
            $testPhone,
            "Bulk message 4: This is your final test message. Sent at " . date('H:i:s'),
            $senderId,
            "bulk-final-{$timestamp}"
        ),
    ];

    echo "   📊 Prepared " . count($messages) . " messages\n\n";

    // Send all messages at once
    echo "📬 Sending bulk messages...\n";
    echo "⚠️  This will send " . count($messages) . " SMS messages to {$testPhone}\n";
    $responses = $client->sendMessages($messages);

    // Process responses
    echo "✅ Bulk send completed! Processing results...\n\n";
    $totalCost = 0;
    $successCount = 0;

    foreach ($responses as $index => $response) {
        $messageNum = $index + 1;
        echo "📨 Message {$messageNum}:\n";
        
        if ($response->isSuccess()) {
            $successCount++;
            $totalCost += $response->getCost();
            echo "   ✅ Status: Sent successfully\n";
            echo "   📨 ID: {$response->getMessageId()}\n";
            echo "   💰 Cost: {$response->getCost()}\n";
            echo "   📱 To: {$response->getTo()}\n";
            echo "   🏷️  Ref: {$response->getCustomRef()}\n";
        } else {
            echo "   ❌ Status: Failed to send\n";
            echo "   📱 To: {$response->getTo()}\n";
        }
        echo "\n";
    }

    // Summary
    echo "📊 Bulk Send Summary:\n";
    echo "====================\n";
    echo "📤 Total messages: " . count($messages) . "\n";
    echo "✅ Successful: {$successCount}\n";
    echo "❌ Failed: " . (count($messages) - $successCount) . "\n";
    echo "💰 Total cost: {$totalCost} credits\n\n";

    // Optional: Check status of first message
    if ($successCount > 0 && $responses[0]->isSuccess()) {
        echo "🔍 Checking status of first message...\n";
        sleep(2); // Wait for processing
        
        $status = $client->getMessageStatus($responses[0]->getMessageId());
        echo "   📊 Status: {$status->getStatus()}\n";
        echo "   📅 Sent: {$status->getSentAt()}\n";
        
        if ($status->getDeliveredAt()) {
            echo "   ✅ Delivered: {$status->getDeliveredAt()}\n";
        }
    }

    echo "\n🎉 Bulk example completed!\n";

} catch (MobileMessageException $e) {
    echo "❌ Mobile Message API Error: " . $e->getMessage() . "\n";
    echo "🔧 Please check your API credentials and try again.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} 