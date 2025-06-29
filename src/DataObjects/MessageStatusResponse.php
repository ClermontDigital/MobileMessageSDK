<?php

declare(strict_types=1);

namespace MobileMessage\DataObjects;

class MessageStatusResponse
{
    private string $messageId;
    private string $to;
    private string $message;
    private string $sender;
    private ?string $customRef;
    private string $status;
    private int $cost;
    private ?string $sentAt;
    private ?string $deliveredAt;

    public function __construct(
        string $messageId,
        string $to,
        string $message,
        string $sender,
        string $status,
        int $cost,
        ?string $customRef = null,
        ?string $sentAt = null,
        ?string $deliveredAt = null
    ) {
        $this->messageId = $messageId;
        $this->to = $to;
        $this->message = $message;
        $this->sender = $sender;
        $this->status = $status;
        $this->cost = $cost;
        $this->customRef = $customRef;
        $this->sentAt = $sentAt;
        $this->deliveredAt = $deliveredAt;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
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

    public function getSentAt(): ?string
    {
        return $this->sentAt;
    }

    public function getDeliveredAt(): ?string
    {
        return $this->deliveredAt;
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['sent', 'pending']);
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'error', 'blocked']);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['message_id'] ?? '',
            $data['to'] ?? '',
            $data['message'] ?? '',
            $data['sender'] ?? '',
            $data['status'] ?? '',
            (int) ($data['cost'] ?? 0),
            $data['custom_ref'] ?? null,
            $data['sent_at'] ?? null,
            $data['delivered_at'] ?? null
        );
    }

    public function toArray(): array
    {
        $data = [
            'message_id' => $this->messageId,
            'to' => $this->to,
            'message' => $this->message,
            'sender' => $this->sender,
            'status' => $this->status,
            'cost' => $this->cost,
        ];

        if ($this->customRef !== null) {
            $data['custom_ref'] = $this->customRef;
        }

        if ($this->sentAt !== null) {
            $data['sent_at'] = $this->sentAt;
        }

        if ($this->deliveredAt !== null) {
            $data['delivered_at'] = $this->deliveredAt;
        }

        return $data;
    }
} 