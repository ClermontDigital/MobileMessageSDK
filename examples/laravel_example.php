<?php

/**
 * Laravel Integration Example
 * 
 * This example shows how to integrate the Mobile Message SDK with Laravel.
 * Follow these steps to set up the integration:
 * 
 * 1. Install the SDK: composer require mobilemessage/php-sdk
 * 2. Add configuration to config/services.php
 * 3. Create a service provider (optional)
 * 4. Use in controllers, jobs, or other Laravel components
 */

// Step 1: Add to config/services.php
/*
'mobile_message' => [
    'username' => env('MOBILE_MESSAGE_USERNAME'),
    'password' => env('MOBILE_MESSAGE_PASSWORD'),
],
*/

// Step 2: Add to .env file
/*
MOBILE_MESSAGE_USERNAME=your_username
MOBILE_MESSAGE_PASSWORD=your_password
*/

// Step 3: Create a Service Provider (app/Providers/MobileMessageServiceProvider.php)
/*
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
*/

// Step 4: Register the provider in config/app.php
/*
'providers' => [
    // ... other providers
    App\Providers\MobileMessageServiceProvider::class,
],
*/

// Step 5: Example Controller
/*
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MobileMessage\MobileMessageClient;
use MobileMessage\DataObjects\Message;
use MobileMessage\Exceptions\MobileMessageException;

class SmsController extends Controller
{
    public function __construct(private MobileMessageClient $smsClient)
    {
    }

    public function sendWelcome(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'name' => 'required|string',
        ]);

        try {
            $response = $this->smsClient->sendMessage(
                $request->phone,
                "Welcome to our app, {$request->name}! Your account is now active.",
                'YourApp',
                'welcome-' . auth()->id()
            );

            if ($response->isSuccess()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Welcome SMS sent successfully',
                    'message_id' => $response->getMessageId(),
                    'cost' => $response->getCost(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send SMS',
                    'status' => $response->getStatus(),
                ], 400);
            }
        } catch (MobileMessageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sendBulkNotification(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'phones' => 'required|array',
            'phones.*' => 'required|string',
        ]);

        $messages = collect($request->phones)->map(function ($phone, $index) use ($request) {
            return new Message(
                $phone,
                $request->message,
                'YourApp',
                'bulk-' . time() . '-' . $index
            );
        })->toArray();

        try {
            $responses = $this->smsClient->sendMessages($messages);
            
            $successful = collect($responses)->filter(fn($r) => $r->isSuccess())->count();
            $failed = count($responses) - $successful;
            $totalCost = collect($responses)->sum(fn($r) => $r->getCost());

            return response()->json([
                'success' => true,
                'total' => count($responses),
                'successful' => $successful,
                'failed' => $failed,
                'total_cost' => $totalCost,
                'results' => $responses,
            ]);
        } catch (MobileMessageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk SMS error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function checkBalance()
    {
        try {
            $balance = $this->smsClient->getBalance();
            
            return response()->json([
                'balance' => $balance->getBalance(),
                'plan' => $balance->getPlan(),
                'has_credits' => $balance->hasCredits(),
            ]);
        } catch (MobileMessageException $e) {
            return response()->json([
                'error' => 'Failed to check balance: ' . $e->getMessage(),
            ], 500);
        }
    }
}
*/

// Step 6: Example Job for Background Processing
/*
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MobileMessage\MobileMessageClient;
use MobileMessage\Exceptions\MobileMessageException;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $phone,
        private string $message,
        private string $sender,
        private ?string $customRef = null
    ) {
    }

    public function handle(MobileMessageClient $smsClient)
    {
        try {
            $response = $smsClient->sendMessage(
                $this->phone,
                $this->message,
                $this->sender,
                $this->customRef
            );

            if ($response->isSuccess()) {
                \Log::info('SMS sent successfully', [
                    'phone' => $this->phone,
                    'message_id' => $response->getMessageId(),
                    'cost' => $response->getCost(),
                ]);
            } else {
                \Log::error('SMS failed to send', [
                    'phone' => $this->phone,
                    'status' => $response->getStatus(),
                ]);
            }
        } catch (MobileMessageException $e) {
            \Log::error('SMS service error', [
                'phone' => $this->phone,
                'error' => $e->getMessage(),
            ]);
            
            throw $e; // Re-throw to trigger job retry
        }
    }
}
*/

// Step 7: Example Routes (routes/web.php or routes/api.php)
/*
Route::post('/sms/welcome', [SmsController::class, 'sendWelcome']);
Route::post('/sms/bulk', [SmsController::class, 'sendBulkNotification']);
Route::get('/sms/balance', [SmsController::class, 'checkBalance']);
*/

// Step 8: Example Notification (using Laravel Notifications)
/*
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use MobileMessage\MobileMessageClient;

class SmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $message, private string $sender = 'YourApp')
    {
    }

    public function via($notifiable)
    {
        return ['sms'];
    }

    public function toSms($notifiable)
    {
        return [
            'phone' => $notifiable->phone,
            'message' => $this->message,
            'sender' => $this->sender,
        ];
    }
}
*/

echo "This is a Laravel integration example file.\n";
echo "Please follow the commented code above to integrate with your Laravel application.\n"; 