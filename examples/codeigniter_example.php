<?php

/**
 * CodeIgniter Integration Example
 * 
 * This example shows how to integrate the Mobile Message SDK with CodeIgniter.
 * Follow these steps to set up the integration:
 * 
 * 1. Install the SDK: composer require mobilemessage/php-sdk
 * 2. Create a library wrapper
 * 3. Configure credentials
 * 4. Use in controllers
 */

// Step 1: Create application/libraries/MobileMessage.php
/*
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '../vendor/autoload.php';

use MobileMessage\MobileMessageClient;
use MobileMessage\DataObjects\Message;
use MobileMessage\Exceptions\MobileMessageException;

class MobileMessage
{
    private $client;
    private $CI;

    public function __construct($params = [])
    {
        $this->CI = &get_instance();
        $this->CI->load->config('mobile_message');
        
        $username = $this->CI->config->item('mobile_message_username');
        $password = $this->CI->config->item('mobile_message_password');
        
        if (!$username || !$password) {
            show_error('Mobile Message credentials not configured');
        }
        
        $this->client = new MobileMessageClient($username, $password);
    }

    public function send_sms($to, $message, $sender, $custom_ref = null)
    {
        try {
            return $this->client->sendMessage($to, $message, $sender, $custom_ref);
        } catch (MobileMessageException $e) {
            log_message('error', 'Mobile Message SMS Error: ' . $e->getMessage());
            return false;
        }
    }

    public function send_bulk_sms($messages)
    {
        try {
            $messageObjects = [];
            foreach ($messages as $msg) {
                $messageObjects[] = new Message(
                    $msg['to'],
                    $msg['message'],
                    $msg['sender'],
                    $msg['custom_ref'] ?? null
                );
            }
            
            return $this->client->sendMessages($messageObjects);
        } catch (MobileMessageException $e) {
            log_message('error', 'Mobile Message Bulk SMS Error: ' . $e->getMessage());
            return false;
        }
    }

    public function get_balance()
    {
        try {
            return $this->client->getBalance();
        } catch (MobileMessageException $e) {
            log_message('error', 'Mobile Message Balance Error: ' . $e->getMessage());
            return false;
        }
    }

    public function get_message_status($message_id)
    {
        try {
            return $this->client->getMessage($message_id);
        } catch (MobileMessageException $e) {
            log_message('error', 'Mobile Message Status Error: ' . $e->getMessage());
            return false;
        }
    }

    public function validate_message($to, $message, $sender)
    {
        try {
            $messageObj = new Message($to, $message, $sender);
            $this->client->validateMessage($messageObj);
            return true;
        } catch (MobileMessageException $e) {
            return $e->getMessage();
        }
    }
}
*/

// Step 2: Create application/config/mobile_message.php
/*
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['mobile_message_username'] = 'your_username';
$config['mobile_message_password'] = 'your_password';

//
*/

// Step 3: Example Controller (application/controllers/Sms.php)
/*
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sms extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('mobilemessage');
        $this->load->helper('url');
    }

    public function index()
    {
        echo "<h1>Mobile Message SMS Integration</h1>";
        echo "<p><a href='" . site_url('sms/send_welcome') . "'>Send Welcome SMS</a></p>";
        echo "<p><a href='" . site_url('sms/send_bulk') . "'>Send Bulk SMS</a></p>";
        echo "<p><a href='" . site_url('sms/check_balance') . "'>Check Balance</a></p>";
    }

    public function send_welcome()
    {
        $phone = '0412345678'; // In real app, get from form/database
        $name = 'John Doe';    // In real app, get from form/database
        
        $message = "Welcome to our service, {$name}! Your account is now active.";
        
        $response = $this->mobilemessage->send_sms(
            $phone,
            $message,
            'YourApp',
            'welcome-' . time()
        );

        if ($response && $response->isSuccess()) {
            echo "<h2>SMS Sent Successfully!</h2>";
            echo "<p>Message ID: " . $response->getMessageId() . "</p>";
            echo "<p>Cost: " . $response->getCost() . " credits</p>";
            echo "<p>Status: " . $response->getStatus() . "</p>";
        } else {
            echo "<h2>Failed to Send SMS</h2>";
            if ($response) {
                echo "<p>Status: " . $response->getStatus() . "</p>";
            } else {
                echo "<p>Service error - check logs</p>";
            }
        }
        
        echo "<p><a href='" . site_url('sms') . "'>Back to SMS Menu</a></p>";
    }

    public function send_bulk()
    {
        $messages = [
            [
                'to' => '0412345678',
                'message' => 'Bulk message 1 - Order confirmation',
                'sender' => 'YourStore',
                'custom_ref' => 'bulk-1-' . time()
            ],
            [
                'to' => '0412345679',
                'message' => 'Bulk message 2 - Appointment reminder',
                'sender' => 'YourStore',
                'custom_ref' => 'bulk-2-' . time()
            ],
            [
                'to' => '0412345680',
                'message' => 'Bulk message 3 - Special offer',
                'sender' => 'YourStore',
                'custom_ref' => 'bulk-3-' . time()
            ]
        ];

        $responses = $this->mobilemessage->send_bulk_sms($messages);

        if ($responses) {
            echo "<h2>Bulk SMS Results</h2>";
            
            $success_count = 0;
            $fail_count = 0;
            $total_cost = 0;
            
            foreach ($responses as $index => $response) {
                $msg_num = $index + 1;
                echo "<h3>Message {$msg_num}:</h3>";
                echo "<p>To: " . $response->getTo() . "</p>";
                echo "<p>Status: " . $response->getStatus() . "</p>";
                echo "<p>Cost: " . $response->getCost() . " credits</p>";
                
                if ($response->isSuccess()) {
                    echo "<p style='color: green;'>✓ Sent successfully (ID: " . $response->getMessageId() . ")</p>";
                    $success_count++;
                } else {
                    echo "<p style='color: red;'>✗ Failed to send</p>";
                    $fail_count++;
                }
                
                $total_cost += $response->getCost();
                echo "<hr>";
            }
            
            echo "<h3>Summary:</h3>";
            echo "<p>Total messages: " . count($responses) . "</p>";
            echo "<p>Successfully sent: {$success_count}</p>";
            echo "<p>Failed: {$fail_count}</p>";
            echo "<p>Total cost: {$total_cost} credits</p>";
        } else {
            echo "<h2>Failed to Send Bulk SMS</h2>";
            echo "<p>Service error - check logs</p>";
        }
        
        echo "<p><a href='" . site_url('sms') . "'>Back to SMS Menu</a></p>";
    }

    public function check_balance()
    {
        $balance = $this->mobilemessage->get_balance();

        if ($balance) {
            echo "<h2>Account Balance</h2>";
            echo "<p>Current balance: " . $balance->getBalance() . " credits</p>";
            echo "<p>Plan: " . $balance->getPlan() . "</p>";
            echo "<p>Has credits: " . ($balance->hasCredits() ? 'Yes' : 'No') . "</p>";
            
            if (!$balance->hasCredits()) {
                echo "<p style='color: red;'>⚠️ Warning: You have no credits remaining!</p>";
            }
        } else {
            echo "<h2>Failed to Check Balance</h2>";
            echo "<p>Service error - check logs</p>";
        }
        
        echo "<p><a href='" . site_url('sms') . "'>Back to SMS Menu</a></p>";
    }

    public function validate_form()
    {
        $this->load->library('form_validation');
        
        $this->form_validation->set_rules('phone', 'Phone Number', 'required');
        $this->form_validation->set_rules('message', 'Message', 'required|max_length[765]');
        $this->form_validation->set_rules('sender', 'Sender ID', 'required');
        
        if ($this->form_validation->run() == FALSE) {
            // Show form with validation errors
            $this->load->view('sms_form');
        } else {
            // Validate with Mobile Message SDK
            $validation_result = $this->mobilemessage->validate_message(
                $this->input->post('phone'),
                $this->input->post('message'),
                $this->input->post('sender')
            );
            
            if ($validation_result === true) {
                echo "<h2>Message Validation Passed</h2>";
                echo "<p>Your message is valid and ready to send.</p>";
                
                // Optionally send the message here
                $response = $this->mobilemessage->send_sms(
                    $this->input->post('phone'),
                    $this->input->post('message'),
                    $this->input->post('sender'),
                    'form-' . time()
                );
                
                if ($response && $response->isSuccess()) {
                    echo "<p style='color: green;'>✓ Message sent successfully!</p>";
                    echo "<p>Message ID: " . $response->getMessageId() . "</p>";
                }
            } else {
                echo "<h2>Message Validation Failed</h2>";
                echo "<p style='color: red;'>Error: {$validation_result}</p>";
            }
        }
        
        echo "<p><a href='" . site_url('sms') . "'>Back to SMS Menu</a></p>";
    }
}
*/

// Step 4: Example View (application/views/sms_form.php)
/*
<!DOCTYPE html>
<html>
<head>
    <title>Send SMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 300px; padding: 8px; border: 1px solid #ddd; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .error { color: red; margin-top: 5px; }
    </style>
</head>
<body>
    <h1>Send SMS Message</h1>
    
    <?= validation_errors('<div class="error">', '</div>') ?>
    
    <?= form_open('sms/validate_form') ?>
        <div class="form-group">
            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" value="<?= set_value('phone') ?>" placeholder="0412345678">
        </div>
        
        <div class="form-group">
            <label for="message">Message (max 765 chars):</label>
            <textarea id="message" name="message" rows="4" placeholder="Enter your message here..."><?= set_value('message') ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="sender">Sender ID:</label>
            <input type="text" id="sender" name="sender" value="<?= set_value('sender') ?>" placeholder="YourApp">
        </div>
        
        <button type="submit">Validate & Send SMS</button>
    <?= form_close() ?>
    
    <p><a href="<?= site_url('sms') ?>">Back to SMS Menu</a></p>
</body>
</html>
*/

// Step 5: Auto-load the library (application/config/autoload.php)
/*
// Add 'mobilemessage' to the libraries array:
$autoload['libraries'] = array('mobilemessage');
*/

echo "This is a CodeIgniter integration example file.\n";
echo "Please follow the commented code above to integrate with your CodeIgniter application.\n"; 