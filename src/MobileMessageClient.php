<?php

declare(strict_types=1);

namespace MobileMessage;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use MobileMessage\DataObjects\Message;
use MobileMessage\DataObjects\MessageResponse;
use MobileMessage\DataObjects\BalanceResponse;
use MobileMessage\DataObjects\MessageStatusResponse;
use MobileMessage\Exceptions\MobileMessageException;
use MobileMessage\Exceptions\AuthenticationException;
use MobileMessage\Exceptions\ValidationException;
use MobileMessage\Exceptions\RateLimitException;

class MobileMessageClient
{
    private const BASE_URL = 'https://api.mobilemessage.com.au/';
    private const MAX_MESSAGES_PER_REQUEST = 100;
    private const MAX_MESSAGE_LENGTH = 765;

    private Client $httpClient;
    private string $username;
    private string $password;

    public function __construct(string $username, string $password, array $httpOptions = [])
    {
        $this->username = $username;
        $this->password = $password;

        $defaultOptions = [
            'base_uri' => self::BASE_URL,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'MobileMessage PHP SDK/1.0.0',
            ],
        ];

        $this->httpClient = new Client(array_merge($defaultOptions, $httpOptions));
    }

    /**
     * Send one or more SMS messages
     *
     * @param Message[] $messages Array of Message objects (max 100)
     * @return MessageResponse[]
     * @throws MobileMessageException
     */
    public function sendMessages(array $messages): array
    {
        if (empty($messages)) {
            throw new ValidationException('Messages array cannot be empty');
        }

        if (count($messages) > self::MAX_MESSAGES_PER_REQUEST) {
            throw new ValidationException(
                sprintf('Maximum %d messages allowed per request', self::MAX_MESSAGES_PER_REQUEST)
            );
        }

        $payload = [
            'messages' => array_map(fn(Message $message) => $message->toArray(), $messages)
        ];

        $response = $this->makeRequest('POST', 'v1/messages', $payload);
        
        return array_map(
            fn(array $result) => MessageResponse::fromArray($result),
            $response['results'] ?? []
        );
    }

    /**
     * Send a single SMS message
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @param string $sender Sender ID
     * @param string|null $customRef Optional custom reference
     * @return MessageResponse
     * @throws MobileMessageException
     */
    public function sendMessage(string $to, string $message, string $sender, ?string $customRef = null): MessageResponse
    {
        $messageObj = new Message($to, $message, $sender, $customRef);
        $responses = $this->sendMessages([$messageObj]);
        
        return $responses[0];
    }

    /**
     * Send SMS using the simple API endpoint
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @param string $sender Sender ID
     * @param string|null $customRef Optional custom reference
     * @return MessageResponse
     * @throws MobileMessageException
     */
    public function sendSimple(string $to, string $message, string $sender, ?string $customRef = null): MessageResponse
    {
        $params = [
            'api_username' => $this->username,
            'api_password' => $this->password,
            'to' => $to,
            'message' => $message,
            'sender' => $sender,
        ];

        if ($customRef !== null) {
            $params['custom_ref'] = $customRef;
        }

        $queryString = http_build_query($params);
        $response = $this->makeRequest('GET', "simple/send-sms?{$queryString}");
        
        return MessageResponse::fromArray($response['results'][0] ?? []);
    }

    /**
     * Lookup a sent message by ID
     *
     * @param string $messageId The message ID to lookup
     * @return MessageStatusResponse
     * @throws MobileMessageException
     */
    public function getMessage(string $messageId): MessageStatusResponse
    {
        $response = $this->makeRequest('GET', "v1/messages/{$messageId}");
        
        return MessageStatusResponse::fromArray($response);
    }

    /**
     * Get account balance
     *
     * @return BalanceResponse
     * @throws MobileMessageException
     */
    public function getBalance(): BalanceResponse
    {
        $response = $this->makeRequest('GET', 'v1/account/balance');
        
        return BalanceResponse::fromArray($response);
    }

    /**
     * Validate a message before sending
     *
     * @param Message $message The message to validate
     * @throws ValidationException
     */
    public function validateMessage(Message $message): void
    {
        if (empty($message->getMessage())) {
            throw new ValidationException('Message content cannot be empty');
        }

        if (strlen($message->getMessage()) > self::MAX_MESSAGE_LENGTH) {
            throw new ValidationException(
                sprintf('Message exceeds maximum length of %d characters', self::MAX_MESSAGE_LENGTH)
            );
        }

        if (empty($message->getTo())) {
            throw new ValidationException('Recipient phone number is required');
        }

        if (empty($message->getSender())) {
            throw new ValidationException('Sender ID is required');
        }

        // Check for non-GSM characters (basic check)
        if (!mb_check_encoding($message->getMessage(), 'ASCII')) {
            throw new ValidationException('Message contains non-GSM characters');
        }
    }

    /**
     * Make an HTTP request to the API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array|null $data Request data
     * @return array
     * @throws MobileMessageException
     */
    private function makeRequest(string $method, string $endpoint, ?array $data = null): array
    {
        try {
            $options = [
                RequestOptions::AUTH => [$this->username, $this->password],
            ];

            if ($data !== null && $method !== 'GET') {
                $options[RequestOptions::JSON] = $data;
            }

            $response = $this->httpClient->request($method, $endpoint, $options);
            $body = $response->getBody()->getContents();
            
            return json_decode($body, true) ?? [];
            
        } catch (GuzzleException $e) {
            $this->handleHttpException($e);
        }
    }

    /**
     * Handle HTTP exceptions and convert to appropriate SDK exceptions
     *
     * @param GuzzleException $exception
     * @throws MobileMessageException
     */
    private function handleHttpException(GuzzleException $exception): void
    {
        $response = $exception->getResponse();
        
        if ($response === null) {
            throw new MobileMessageException('Network error: ' . $exception->getMessage(), 0, $exception);
        }

        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        switch ($statusCode) {
            case 401:
                throw new AuthenticationException('Invalid API credentials');
            case 403:
                throw new ValidationException('Insufficient credits or permission denied');
            case 429:
                throw new RateLimitException('Too many concurrent requests. Please wait.');
            case 400:
                $errorData = json_decode($body, true);
                $message = $errorData['message'] ?? 'Bad request';
                throw new ValidationException($message);
            default:
                throw new MobileMessageException(
                    sprintf('API request failed with status %d: %s', $statusCode, $body)
                );
        }
    }
} 