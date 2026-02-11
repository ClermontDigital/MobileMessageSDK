<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/helpers.php';

use MobileMessage\MobileMessageClient;
use MobileMessage\Exceptions\MobileMessageException;

/**
 * Basic Example - Sending a Single SMS
 *
 * This example shows how to send a single SMS message using the Mobile Message PHP SDK.
 *
 * Setup:
 * 1. Copy .env.example to .env in the project root
 * 2. Add your Mobile Message API credentials
 * 3. Run: php examples/basic_example.php
 */

try {
    echo "📱 Mobile Message PHP SDK - Basic Example\n";
    echo "=========================================\n\n";

    // Load credentials from .env
    loadEnv(__DIR__ . '/../.env');
    
    $username = $_ENV['API_USERNAME'] ?? null;
    $password = $_ENV['API_PASSWORD'] ?? null;
    $testPhone = $_ENV['TEST_PHONE_NUMBER'] ?? '0400322583';
    $senderId = $_ENV['SENDER_PHONE_NUMBER'] ?? null;

    if (!$username || !$password || $username === 'your_api_username_here') {
        throw new Exception('Please configure your API credentials in the .env file');
    }

    if (!$senderId) {
        throw new Exception('Please configure your SENDER_PHONE_NUMBER in the .env file');
    }

    // Initialise the client
    echo "🔧 Initialising client...\n";
    $client = new MobileMessageClient($username, $password);

    // Check account balance first
    echo "💰 Checking account balance...\n";
    $balance = $client->getBalance();
    echo "   Balance: {$balance->getBalance()} credits\n";
    echo "   Plan: {$balance->getPlan()}\n\n";

    // Check if real SMS is enabled
    $enableRealSms = filter_var($_ENV['ENABLE_REAL_SMS_TESTS'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    
    if (!$enableRealSms) {
        echo "⚠️  Real SMS sending is disabled.\n";
        echo "   Set ENABLE_REAL_SMS_TESTS=true in .env to send actual SMS messages.\n";
        echo "   This example will only check your credentials and balance.\n\n";
        echo "✅ Basic example completed successfully!\n";
        exit(0);
    }

    // Send SMS message
    echo "📱 Sending SMS message...\n";
    $response = $client->sendMessage(
        $testPhone,
        'Hello from Mobile Message PHP SDK! Sent at ' . date('Y-m-d H:i:s'),
        $senderId,
        'basic-example-' . time()
    );

    // Display results
    if ($response->isSuccess()) {
        echo "✅ Message sent successfully!\n";
        echo "   📨 Message ID: {$response->getMessageId()}\n";
        echo "   💰 Cost: {$response->getCost()}\n";
        echo "   📱 To: {$response->getTo()}\n";
        echo "   👤 From: {$response->getSender()}\n";
        echo "   🏷️  Reference: {$response->getCustomRef()}\n\n";

        // Optional: Check message status
        echo "🔍 Checking message status...\n";
        sleep(2); // Wait for processing
        
        $status = $client->getMessage($response->getMessageId());
        echo "   📊 Status: {$status->getStatus()}\n";
        echo "   📅 Sent: {$status->getSentAt()}\n";
        
        if ($status->getDeliveredAt()) {
            echo "   ✅ Delivered: {$status->getDeliveredAt()}\n";
        }
    } else {
        echo "❌ Failed to send message\n";
    }

    echo "\n🎉 Basic example completed!\n";

} catch (MobileMessageException $e) {
    echo "❌ Mobile Message API Error: " . $e->getMessage() . "\n";
    echo "🔧 Please check your API credentials and try again.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} 