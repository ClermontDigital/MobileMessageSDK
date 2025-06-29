<?php

declare(strict_types=1);

namespace MobileMessage\DataObjects;

class Message
{
    private string $to;
    private string $message;
    private string $sender;
    private ?string $customRef;

    public function __construct(string $to, string $message, string $sender, ?string $customRef = null)
    {
        $this->to = $to;
        $this->message = $message;
        $this->sender = $sender;
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

    public function toArray(): array
    {
        $data = [
            'to' => $this->to,
            'message' => $this->message,
            'sender' => $this->sender,
        ];

        if ($this->customRef !== null) {
            $data['custom_ref'] = $this->customRef;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['to'] ?? '',
            $data['message'] ?? '',
            $data['sender'] ?? '',
            $data['custom_ref'] ?? null
        );
    }
} 