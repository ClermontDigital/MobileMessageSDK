<?php

declare(strict_types=1);

namespace MobileMessage\Tests\Unit;

use MobileMessage\DataObjects\BalanceResponse;
use PHPUnit\Framework\TestCase;

class BalanceResponseTest extends TestCase
{
    public function testBalanceResponseConstruction(): void
    {
        $response = new BalanceResponse(250, 'Premium Plan');

        $this->assertEquals(250, $response->getBalance());
        $this->assertEquals('Premium Plan', $response->getPlan());
    }

    public function testHasCredits(): void
    {
        $responseWithCredits = new BalanceResponse(100, 'Basic Plan');
        $this->assertTrue($responseWithCredits->hasCredits());

        $responseWithoutCredits = new BalanceResponse(0, 'Basic Plan');
        $this->assertFalse($responseWithoutCredits->hasCredits());

        $responseNegative = new BalanceResponse(-5, 'Basic Plan');
        $this->assertFalse($responseNegative->hasCredits());
    }

    public function testFromArray(): void
    {
        $data = [
            'balance' => 150,
            'plan' => 'Standard Plan',
        ];

        $response = BalanceResponse::fromArray($data);

        $this->assertEquals(150, $response->getBalance());
        $this->assertEquals('Standard Plan', $response->getPlan());
    }

    public function testFromArrayWithMissingFields(): void
    {
        $data = [];

        $response = BalanceResponse::fromArray($data);

        $this->assertEquals(0, $response->getBalance());
        $this->assertEquals('', $response->getPlan());
    }

    public function testFromArrayWithStringBalance(): void
    {
        $data = [
            'balance' => '75',
            'plan' => 'Basic Plan',
        ];

        $response = BalanceResponse::fromArray($data);

        $this->assertEquals(75, $response->getBalance());
        $this->assertEquals('Basic Plan', $response->getPlan());
    }

    public function testToArray(): void
    {
        $response = new BalanceResponse(300, 'Enterprise Plan');
        $array = $response->toArray();

        $expected = [
            'balance' => 300,
            'plan' => 'Enterprise Plan',
        ];

        $this->assertEquals($expected, $array);
    }
} 