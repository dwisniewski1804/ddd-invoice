<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

/**
 * Email value object.
 * 
 * Encapsulates email validation and ensures type safety.
 */
final class Email
{
    public function __construct(
        public string $value,
    ) {
        if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: {$this->value}");
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

