# Mobile Message PHP SDK

[![Latest Version](https://img.shields.io/packagist/v/mobilemessage/php-sdk.svg?style=flat-square)](https://packagist.org/packages/mobilemessage/php-sdk)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/ClermontDigital/MobileMessageSDK/tests.yml?branch=main&style=flat-square)](https://github.com/ClermontDigital/MobileMessageSDK/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/mobilemessage/php-sdk.svg?style=flat-square)](https://packagist.org/packages/mobilemessage/php-sdk)
[![GitHub Stars](https://img.shields.io/github/stars/ClermontDigital/MobileMessageSDK.svg?style=flat-square)](https://github.com/ClermontDigital/MobileMessageSDK/stargazers)

An **unofficial** PHP SDK for the [Mobile Message SMS API](https://mobilemessage.com.au/). Send SMS messages, track delivery status, and manage your messaging campaigns with Australia's leading SMS service.

## Features

- ✅ Send single and bulk SMS messages
- ✅ Track message delivery status
- ✅ Check account balance
- ✅ Simple and Advanced API endpoints
- ✅ Comprehensive error handling
- ✅ Laravel and CodeIgniter compatible
- ✅ PSR-4 autoloading
- ✅ Extensive test coverage
- ✅ Type-safe with PHP 7.4+ support

## Installation

Install the SDK via Composer:

```bash
composer require mobilemessage/php-sdk
```

## Requirements

- PHP 7.4 or higher
- Guzzle HTTP client
- Mobile Message API credentials

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use MobileMessage\MobileMessageClient;
use MobileMessage\DataObjects\Message;

// Initialise the client
$client = new MobileMessageClient('your_username', 'your_password');

// Send a single SMS
$response = $client->sendMessage(
    '0412345678',                    // recipient
    'Hello from Mobile Message!',    // message
    'YourCompany',                   // sender ID
    'optional-reference'             // custom reference (optional)
);

if ($response->isSuccess()) {
    echo "Message sent! ID: " . $response->getMessageId();
} else {
    echo "Failed to send: " . $response->getStatus();
}
```

## Usage

### Basic Configuration

```php
use MobileMessage\MobileMessageClient;

$client = new MobileMessageClient('your_username', 'your_password');

// Optional: Configure HTTP client options
$client = new MobileMessageClient('your_username', 'your_password', [
    'timeout' => 60,
    'connect_timeout' => 10,
]);
```

### Sending Messages

#### Single Message

```php
$response = $client->sendMessage(
    '0412345678',
    'Your verification code is 1234',
    'YourApp'
);

echo "Status: " . $response->getStatus() . "\n";
echo "Message ID: " . $response->getMessageId() . "\n";
echo "Cost: " . $response->getCost() . " credits\n";
```

#### Bulk Messages

```php
use MobileMessage\DataObjects\Message;

$messages = [
    new Message('0412345678', 'Message 1', 'YourApp', 'ref1'),
    new Message('0412345679', 'Message 2', 'YourApp', 'ref2'),
    new Message('0412345680', 'Message 3', 'YourApp', 'ref3'),
];

$responses = $client->sendMessages($messages);

foreach ($responses as $response) {
    echo "To: {$response->getTo()}, Status: {$response->getStatus()}\n";
}
```

#### Simple API (for basic use cases)

```php
$response = $client->sendSimple(
    '61412345678',              // international format
    'Hello World!',
    'YourApp'
);
```

### Checking Account Balance

```php
$balance = $client->getBalance();

echo "Current balance: " . $balance->getBalance() . " credits\n";
echo "Plan: " . $balance->getPlan() . "\n";

if ($balance->hasCredits()) {
    echo "You have credits available\n";
}
```

### Message Status Tracking

```php
// Get message status by ID
$messageId = 'your-message-id-here';
$status = $client->getMessage($messageId);

echo "Message Status: " . $status->getStatus() . "\n";
echo "Delivered: " . ($status->isDelivered() ? 'Yes' : 'No') . "\n";
echo "Failed: " . ($status->isFailed() ? 'Yes' : 'No') . "\n";

if ($status->getDeliveredAt()) {
    echo "Delivered at: " . $status->getDeliveredAt() . "\n";
}
```

### Message Validation

```php
use MobileMessage\DataObjects\Message;
use MobileMessage\Exceptions\ValidationException;

$message = new Message('0412345678', 'Test message', 'Sender');

try {
    $client->validateMessage($message);
    echo "Message is valid\n";
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
}
```

## Laravel Integration

### Service Provider Registration

Add to your `config/app.php`:

```php
// Create a custom service provider
'providers' => [
    // ... other providers
    App\Providers\MobileMessageServiceProvider::class,
],
```

### Service Provider Implementation

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MobileMessage\MobileMessageClient;

class MobileMessageServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(MobileMessageClient::class, function ($app) {
            return new MobileMessageClient(
                config('services.mobile_message.username'),
                config('services.mobile_message.password')
            );
        });
    }
}
```

### Configuration

Add to your `config/services.php`:

```php
'mobile_message' => [
    'username' => env('MOBILE_MESSAGE_USERNAME'),
    'password' => env('MOBILE_MESSAGE_PASSWORD'),
],
```

### Usage in Laravel

```php
<?php

namespace App\Http\Controllers;

use MobileMessage\MobileMessageClient;

class SmsController extends Controller
{
    public function __construct(private MobileMessageClient $smsClient)
    {
    }

    public function sendNotification(Request $request)
    {
        $response = $this->smsClient->sendMessage(
            $request->phone,
            $request->message,
            'YourApp'
        );

        return response()->json([
            'success' => $response->isSuccess(),
            'message_id' => $response->getMessageId(),
        ]);
    }
}
```

## CodeIgniter Integration

### Library Setup

Create `application/libraries/MobileMessage.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '../vendor/autoload.php';

use MobileMessage\MobileMessageClient;

class MobileMessage
{
    private $client;

    public function __construct($params = [])
    {
        $CI = &get_instance();
        $CI->load->config('mobile_message');
        
        $this->client = new MobileMessageClient(
            $CI->config->item('mobile_message_username'),
            $CI->config->item('mobile_message_password')
        );
    }

    public function send_sms($to, $message, $sender, $custom_ref = null)
    {
        return $this->client->sendMessage($to, $message, $sender, $custom_ref);
    }

    public function get_balance()
    {
        return $this->client->getBalance();
    }
}
```

### Configuration

Create `application/config/mobile_message.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['mobile_message_username'] = 'your_username';
$config['mobile_message_password'] = 'your_password';
```

### Usage in CodeIgniter

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sms extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('mobilemessage');
    }

    public function send_notification()
    {
        $response = $this->mobilemessage->send_sms(
            '0412345678',
            'Your order has been confirmed!',
            'YourStore'
        );

        if ($response->isSuccess()) {
            echo "SMS sent successfully!";
        } else {
            echo "Failed to send SMS: " . $response->getStatus();
        }
    }
}
```

## Error Handling

The SDK provides specific exception types for different error conditions:

```php
use MobileMessage\Exceptions\AuthenticationException;
use MobileMessage\Exceptions\ValidationException;
use MobileMessage\Exceptions\RateLimitException;
use MobileMessage\Exceptions\MobileMessageException;

try {
    $response = $client->sendMessage('0412345678', 'Test', 'Sender');
} catch (AuthenticationException $e) {
    echo "Authentication failed: " . $e->getMessage();
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage();
} catch (RateLimitException $e) {
    echo "Rate limit exceeded: " . $e->getMessage();
} catch (MobileMessageException $e) {
    echo "API error: " . $e->getMessage();
}
```

## Testing

### Quick Start Testing Setup

For easy testing with your real Mobile Message API credentials:

```bash
# Run the interactive setup script
./setup-testing.sh
```

This will:
- Create a `.env` file with your API credentials
- Configure test phone number and sender ID
- Set up safety controls for real SMS testing

### Manual Testing Setup

1. Copy the environment template:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` with your credentials:
   ```env
   API_USERNAME=your_api_username
   API_PASSWORD=your_api_password
   TEST_PHONE_NUMBER=0400322583
   SENDER_PHONE_NUMBER=your_sender_phone
   ENABLE_REAL_SMS_TESTS=false  # Set to true to send real SMS
   ENABLE_BULK_SMS_TESTS=false  # Set to true to enable bulk testing
   ```

### Running Tests

```bash
# Unit tests only (safe, no API calls)
composer test

# Integration tests (requires valid .env credentials)
composer test -- --testsuite Integration

# Test coverage report
composer test-coverage

# Comprehensive test script with real API
php examples/test_example.php

# Test individual examples
php examples/basic_example.php
php examples/bulk_example.php
```

**⚠️ Important:** Integration tests with `ENABLE_REAL_SMS_TESTS=true` will send actual SMS messages and use credits from your Mobile Message account.

## Examples

See the `examples/` directory for complete working examples:

- [Basic SMS sending](examples/basic_example.php)
- [Bulk messaging](examples/bulk_example.php)
- [Laravel integration](examples/laravel_example.php)
- [CodeIgniter integration](examples/codeigniter_example.php)

## API Reference

### MobileMessageClient

| Method | Description | Parameters | Returns |
|--------|-------------|------------|---------|
| `sendMessage()` | Send a single SMS | `$to, $message, $sender, $customRef?` | `MessageResponse` |
| `sendMessages()` | Send multiple SMS | `Message[]` | `MessageResponse[]` |
| `sendSimple()` | Send via simple API | `$to, $message, $sender, $customRef?` | `MessageResponse` |
| `getMessage()` | Get message status | `$messageId` | `MessageStatusResponse` |
| `getBalance()` | Get account balance | - | `BalanceResponse` |
| `validateMessage()` | Validate message | `Message` | `void` (throws on error) |

### Data Objects

- **Message**: Input message data
- **MessageResponse**: API response for sent messages
- **MessageStatusResponse**: Message status lookup response
- **BalanceResponse**: Account balance information

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Disclaimer

This is an **unofficial** SDK for the Mobile Message API. It is not affiliated with or endorsed by Mobile Message Pty Ltd. For official support and documentation, please visit [Mobile Message](https://mobilemessage.com.au/).

## Support

- [Mobile Message API Documentation](https://mobilemessage.com.au/api-documentation)
- [Mobile Message Support](https://mobilemessage.com.au/contact)
- [SDK Issues](https://github.com/ClermontDigital/MobileMessageSDK/issues) 