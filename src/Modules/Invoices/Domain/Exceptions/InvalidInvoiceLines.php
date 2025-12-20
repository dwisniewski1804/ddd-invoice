<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

/**
 * Exception thrown when invoice lines have invalid data.
 */
final class InvalidInvoiceLines extends \DomainException
{
    public static function invalidQuantity(int $quantity): self
    {
        return new self(
            message: sprintf(
                'Invoice line quantity must be greater than zero, got %d.',
                $quantity
            )
        );
    }

    public static function invalidUnitPrice(int $unitPrice): self
    {
        return new self(
            message: sprintf(
                'Invoice line unit price must be greater than zero, got %d.',
                $unitPrice
            )
        );
    }
}

