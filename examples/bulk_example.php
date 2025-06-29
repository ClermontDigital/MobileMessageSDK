<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MobileMessage\MobileMessageClient;
use MobileMessage\DataObjects\Message;
use MobileMessage\Exceptions\MobileMessageException;

// Initialise the client
$client = new MobileMessageClient('your_username', 'your_password');

try {
    echo "Mobile Message PHP SDK - Bulk Messaging Example\n";
    echo "===============================================\n\n";

    // Check balance first
    $balance = $client->getBalance();
    echo "Account balance: {$balance->getBalance()} credits\n\n";

    // Prepare multiple messages
    $messages = [
        new Message(
            '0412345678',
            'Welcome to our service! Your account has been activated.',
            'YourApp',
            'welcome-001'
        ),
        new Message(
            '0412345679',
            'Reminder: Your appointment is scheduled for tomorrow at 2 PM.',
            'YourApp',
            'reminder-002'
        ),
        new Message(
            '0412345680',
            'Thank you for your purchase! Your order #12345 is being processed.',
            'YourApp',
            'order-003'
        ),
        new Message(
            '0412345681',
            'Your verification code is: 789123. This code expires in 10 minutes.',
            'YourApp',
            'verify-004'
        ),
    ];

    echo "Sending {" . count($messages) . "} messages...\n\n";

    // Send all messages in one request
    $responses = $client->sendMessages($messages);

    $successCount = 0;
    $failCount = 0;
    $totalCost = 0;

    foreach ($responses as $index => $response) {
        $messageNum = $index + 1;
        echo "Message {$messageNum}:\n";
        echo "  To: {$response->getTo()}\n";
        echo "  Status: {$response->getStatus()}\n";
        echo "  Cost: {$response->getCost()} credits\n";
        
        if ($response->isSuccess()) {
            echo "  ✅ Sent successfully (ID: {$response->getMessageId()})\n";
            $successCount++;
        } else {
            echo "  ❌ Failed to send\n";
            $failCount++;
        }
        
        $totalCost += $response->getCost();
        echo "  Custom Ref: {$response->getCustomRef()}\n\n";
    }

    // Summary
    echo "Summary:\n";
    echo "========\n";
    echo "Total messages: " . count($responses) . "\n";
    echo "Successful: {$successCount}\n";
    echo "Failed: {$failCount}\n";
    echo "Total cost: {$totalCost} credits\n";

    // Check remaining balance
    $newBalance = $client->getBalance();
    echo "Remaining balance: {$newBalance->getBalance()} credits\n";

} catch (MobileMessageException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nBulk messaging example completed!\n"; 