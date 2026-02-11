<?php

declare(strict_types=1);

namespace MobileMessage\DataObjects;

class MessageResponse
{
    private string $to;
    private string $message;
    private string $sender;
    private ?string $customRef;
    private string $status;
    private int $cost;
    private string $messageId;

    public function __construct(
        string $to,
        string $message,
        string $sender,
        string $status,
        int $cost,
        string $messageId,
        ?string $customRef = null
    ) {
        $this->to = $to;
        $this->message = $message;
        $this->sender = $sender;
        $this->status = $status;
        $this->cost = $cost;
        $this->messageId = $messageId;
        $this->customRef = $customRef;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getCustomRef(): ?string
    {
        return $this->customRef;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCost(): int
    {
        return $this->cost;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isError(): bool
    {
        return in_array($this->status, ['error', 'blocked', 'failed'], true);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['to'] ?? '',
            $data['message'] ?? '',
            $data['sender'] ?? '',
            $data['status'] ?? '',
            (int) ($data['cost'] ?? 0),
            $data['message_id'] ?? '',
            $data['custom_ref'] ?? null
        );
    }

    public function toArray(): array
    {
        $data = [
            'to' => $this->to,
            'message' => $this->message,
            'sender' => $this->sender,
            'status' => $this->status,
            'cost' => $this->cost,
            'message_id' => $this->messageId,
        ];

        if ($this->customRef !== null) {
            $data['custom_ref'] = $this->customRef;
        }

        return $data;
    }
} 