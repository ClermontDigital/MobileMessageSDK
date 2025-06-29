<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MobileMessage\MobileMessageClient;
use MobileMessage\Exceptions\MobileMessageException;

// Initialise the client with your credentials
$client = new MobileMessageClient('your_username', 'your_password');

try {
    echo "Mobile Message PHP SDK - Basic Example\n";
    echo "=====================================\n\n";

    // Check account balance first
    echo "1. Checking account balance...\n";
    $balance = $client->getBalance();
    echo "   Balance: {$balance->getBalance()} credits\n";
    echo "   Plan: {$balance->getPlan()}\n\n";

    if (!$balance->hasCredits()) {
        echo "   ⚠️  Warning: You have no credits remaining!\n\n";
    }

    // Send a single SMS message
    echo "2. Sending SMS message...\n";
    $response = $client->sendMessage(
        '0412345678',                           // recipient phone number
        'Hello! This is a test message from the Mobile Message PHP SDK.',
        'TestApp',                              // sender ID (must be approved)
        'example-' . time()                     // custom reference for tracking
    );

    if ($response->isSuccess()) {
        echo "   ✅ Message sent successfully!\n";
        echo "   Message ID: {$response->getMessageId()}\n";
        echo "   Cost: {$response->getCost()} credits\n";
        echo "   Status: {$response->getStatus()}\n\n";

        // Wait a moment then check message status
        echo "3. Checking message status...\n";
        sleep(2); // Wait 2 seconds
        
        $status = $client->getMessage($response->getMessageId());
        echo "   Current status: {$status->getStatus()}\n";
        echo "   Delivered: " . ($status->isDelivered() ? 'Yes' : 'No') . "\n";
        echo "   Failed: " . ($status->isFailed() ? 'Yes' : 'No') . "\n";
        
        if ($status->getSentAt()) {
            echo "   Sent at: {$status->getSentAt()}\n";
        }
        
        if ($status->getDeliveredAt()) {
            echo "   Delivered at: {$status->getDeliveredAt()}\n";
        }
        
    } else {
        echo "   ❌ Failed to send message\n";
        echo "   Status: {$response->getStatus()}\n";
        echo "   Error: " . ($response->isError() ? 'Yes' : 'No') . "\n";
    }

} catch (MobileMessageException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please check your credentials and try again.\n";
}

echo "\nExample completed!\n"; 