<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    /**
     * Given: A valid email address string
     * When: Email value object is created
     * Then: Email is created successfully with correct value
     */
    public function test_create_valid_email(): void
    {
        $email = new Email('john@example.com');

        $this->assertSame('john@example.com', $email->value);
        $this->assertSame('john@example.com', (string) $email);
    }

    /**
     * Given: A valid email address with subdomain
     * When: Email value object is created
     * Then: Email is created successfully
     */
    public function test_create_valid_email_with_subdomain(): void
    {
        $email = new Email('user@mail.example.com');

        $this->assertSame('user@mail.example.com', $email->value);
    }

    /**
     * Given: A valid email address with plus sign (tagging)
     * When: Email value object is created
     * Then: Email is created successfully
     */
    public function test_create_valid_email_with_plus_sign(): void
    {
        $email = new Email('user+tag@example.com');

        $this->assertSame('user+tag@example.com', $email->value);
    }

    /**
     * Given: A valid email address with numbers in local part and domain
     * When: Email value object is created
     * Then: Email is created successfully
     */
    public function test_create_valid_email_with_numbers(): void
    {
        $email = new Email('user123@example123.com');

        $this->assertSame('user123@example123.com', $email->value);
    }

    /**
     * Given: An email string without @ sign
     * When: Email value object is created
     * Then: InvalidArgumentException is thrown
     */
    public function test_cannot_create_email_without_at_sign(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: invalidemail.com');

        new Email('invalidemail.com');
    }

    /**
     * Given: An email string without domain part
     * When: Email value object is created
     * Then: InvalidArgumentException is thrown
     */
    public function test_cannot_create_email_without_domain(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: user@');

        new Email('user@');
    }

    /**
     * Given: An email string without local part (before @)
     * When: Email value object is created
     * Then: InvalidArgumentException is thrown
     */
    public function test_cannot_create_email_without_local_part(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: @example.com');

        new Email('@example.com');
    }

    /**
     * Given: An email string containing spaces
     * When: Email value object is created
     * Then: InvalidArgumentException is thrown
     */
    public function test_cannot_create_email_with_spaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: user name@example.com');

        new Email('user name@example.com');
    }

    /**
     * Given: A completely invalid email format string
     * When: Email value object is created
     * Then: InvalidArgumentException is thrown
     */
    public function test_cannot_create_email_with_invalid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: notanemail');

        new Email('notanemail');
    }

    /**
     * Given: A valid Email value object
     * When: __toString() is called or cast to string
     * Then: Returns the email value string
     */
    public function test_to_string_returns_email_value(): void
    {
        $email = new Email('test@example.com');

        $this->assertSame('test@example.com', $email->__toString());
        $this->assertSame('test@example.com', (string) $email);
    }
}
