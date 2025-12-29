<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

/**
 * Customer value object.
 *
 * Represents customer information (name and email).
 */
final class Customer
{
    public function __construct(
        public string $name,
        public Email $email,
    ) {}
}
