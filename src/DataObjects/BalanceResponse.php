<?php

declare(strict_types=1);

namespace MobileMessage\DataObjects;

class BalanceResponse
{
    private int $balance;
    private string $plan;

    public function __construct(int $balance, string $plan)
    {
        $this->balance = $balance;
        $this->plan = $plan;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getPlan(): string
    {
        return $this->plan;
    }

    public function hasCredits(): bool
    {
        return $this->balance > 0;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['balance'] ?? 0),
            $data['plan'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'balance' => $this->balance,
            'plan' => $this->plan,
        ];
    }
} 