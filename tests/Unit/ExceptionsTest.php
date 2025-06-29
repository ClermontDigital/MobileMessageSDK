<?php

declare(strict_types=1);

namespace MobileMessage\Tests\Unit;

use MobileMessage\Exceptions\MobileMessageException;
use MobileMessage\Exceptions\AuthenticationException;
use MobileMessage\Exceptions\ValidationException;
use MobileMessage\Exceptions\RateLimitException;
use PHPUnit\Framework\TestCase;
use Exception;

class ExceptionsTest extends TestCase
{
    public function testMobileMessageException(): void
    {
        $message = 'Test mobile message exception';
        $code = 100;
        $previous = new Exception('Previous exception');

        $exception = new MobileMessageException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($previous, $exception->getPrevious());
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testMobileMessageExceptionInheritsFromException(): void
    {
        $exception = new MobileMessageException('Test message');
        
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertInstanceOf(MobileMessageException::class, $exception);
    }

    public function testAuthenticationException(): void
    {
        $message = 'Authentication failed';
        $code = 401;

        $exception = new AuthenticationException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertInstanceOf(MobileMessageException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testAuthenticationExceptionInheritance(): void
    {
        $exception = new AuthenticationException('Invalid credentials');
        
        $this->assertInstanceOf(MobileMessageException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testValidationException(): void
    {
        $message = 'Validation error occurred';
        $code = 400;

        $exception = new ValidationException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertInstanceOf(MobileMessageException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testValidationExceptionInheritance(): void
    {
        $exception = new ValidationException('Invalid message format');
        
        $this->assertInstanceOf(MobileMessageException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testRateLimitException(): void
    {
        $message = 'Rate limit exceeded';
        $code = 429;

        $exception = new RateLimitException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertInstanceOf(MobileMessageException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testRateLimitExceptionInheritance(): void
    {
        $exception = new RateLimitException('Too many requests');
        
        $this->assertInstanceOf(MobileMessageException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHierarchy(): void
    {
        // Test that all custom exceptions inherit from MobileMessageException
        $authException = new AuthenticationException('Auth error');
        $validationException = new ValidationException('Validation error');
        $rateLimitException = new RateLimitException('Rate limit error');

        $this->assertInstanceOf(MobileMessageException::class, $authException);
        $this->assertInstanceOf(MobileMessageException::class, $validationException);
        $this->assertInstanceOf(MobileMessageException::class, $rateLimitException);

        // Test that MobileMessageException inherits from base Exception
        $baseException = new MobileMessageException('Base error');
        $this->assertInstanceOf(Exception::class, $baseException);
    }

    public function testExceptionWithPreviousException(): void
    {
        $previousException = new Exception('Original error');
        $mobileMessageException = new MobileMessageException('Wrapped error', 0, $previousException);

        $this->assertEquals($previousException, $mobileMessageException->getPrevious());

        // Test inheritance with previous exception
        $authException = new AuthenticationException('Auth failed', 401, $previousException);
        $this->assertEquals($previousException, $authException->getPrevious());
    }
} 