<?php

declare(strict_types=1);

namespace MobileMessage;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
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
    private const DEFAULT_BASE_URL = 'https://api.mobilemessage.com.au/';
    private const SDK_VERSION = '1.0.5';
    private const MAX_MESSAGES_PER_REQUEST = 100;
    private const MAX_MESSAGE_LENGTH = 765;

    private Client $httpClient;
    private string $username;
    private string $password;

    /**
     * @param string $username API username
     * @param string $password API password
     * @param array $httpOptions Guzzle HTTP options (e.g., ['base_uri' => 'https://staging.api.example.com/'] to override default)
     */
    public function __construct(string $username, string $password, array $httpOptions = [])
    {
        if (empty(trim($username))) {
            throw new ValidationException('API username cannot be empty');
        }
        if (empty(trim($password))) {
            throw new ValidationException('API password cannot be empty');
        }

        $this->username = $username;
        $this->password = $password;

        $defaultOptions = [
            'base_uri' => self::DEFAULT_BASE_URL,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'MobileMessage PHP SDK/' . self::SDK_VERSION,
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

        foreach ($messages as $message) {
            $this->validateMessage($message);
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

        if (empty($responses)) {
            throw new MobileMessageException('API returned no results for the sent message');
        }

        return $responses[0];
    }

    /**
     * Send SMS using the simple API endpoint
     *
     * WARNING: This method sends credentials as query parameters which may be
     * logged by proxies, CDNs, and server access logs. Use sendMessage() instead
     * for better security (uses HTTP Basic Auth).
     *
     * @deprecated Use sendMessage() instead for secure credential handling.
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @param string $sender Sender ID
     * @param string|null $customRef Optional custom reference
     * @return MessageResponse
     * @throws MobileMessageException
     */
    public function sendSimple(string $to, string $message, string $sender, ?string $customRef = null): MessageResponse
    {
        @trigger_error(
            'sendSimple() sends credentials as query parameters. Use sendMessage() for better security.',
            E_USER_DEPRECATED
        );

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

        $response = $this->makeRequest('GET', 'simple/send-sms', null, $params);

        // The simple API may return results nested under 'results' or as a flat response
        if (!empty($response['results'])) {
            return MessageResponse::fromArray($response['results'][0]);
        }

        // Fall back to treating the entire response as the message data
        if (!empty($response)) {
            return MessageResponse::fromArray($response);
        }

        throw new MobileMessageException('API returned no results for the simple send request');
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
        if (empty(trim($messageId))) {
            throw new ValidationException('Message ID cannot be empty');
        }

        $response = $this->makeRequest('GET', 'v1/messages', null, ['message_id' => $messageId]);

        if (empty($response['results'])) {
            throw new MobileMessageException(
                sprintf('Message not found: %s', $messageId)
            );
        }

        return MessageStatusResponse::fromArray($response['results'][0]);
    }

    /**
     * Get account balance
     *
     * @return BalanceResponse
     *
     * @throws MobileMessageException
     */
    public function getBalance(): BalanceResponse
    {
        $response = $this->makeRequest('GET', 'v1/account');
        
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

        if (mb_strlen($message->getMessage()) > self::MAX_MESSAGE_LENGTH) {
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
    }

    /**
     * Make an HTTP request to the API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array|null $data Request body data (for POST/PUT)
     * @param array|null $queryParams Query string parameters (for GET)
     * @return array
     * @throws MobileMessageException
     */
    private function makeRequest(string $method, string $endpoint, ?array $data = null, ?array $queryParams = null): array
    {
        try {
            $options = [
                RequestOptions::AUTH => [$this->username, $this->password],
            ];

            if ($data !== null && $method !== 'GET') {
                $options[RequestOptions::JSON] = $data;
            }

            if ($queryParams !== null) {
                $options[RequestOptions::QUERY] = $queryParams;
            }

            $response = $this->httpClient->request($method, $endpoint, $options);
            $body = $response->getBody()->getContents();

            $decoded = json_decode($body, true);
            if ($decoded === null && $body !== '' && $body !== 'null') {
                throw new MobileMessageException(
                    'API returned invalid JSON response: ' . substr($body, 0, 200)
                );
            }

            return $decoded ?? [];

        } catch (GuzzleException $e) {
            $this->handleHttpException($e);
            throw new MobileMessageException('Unexpected error during API request', 0, $e);
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
        if (!$exception instanceof RequestException) {
            throw new MobileMessageException(
                'Network error: ' . $exception->getMessage(),
                0,
                $exception
            );
        }

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