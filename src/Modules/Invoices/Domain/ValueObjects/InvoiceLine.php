<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidInvoiceLines;
use Ramsey\Uuid\UuidInterface;

/**
 * Invoice line value object.
 *
 * Represents a single line item on an invoice with validation:
 * - Quantity must be positive (> 0)
 * - Unit price must be positive (> 0)
 */
final class InvoiceLine
{
    public function __construct(
        public UuidInterface $id,
        public string $name,
        public int $quantity,
        public int $unitPrice,
    ) {
        if ($quantity <= 0) {
            throw InvalidInvoiceLines::invalidQuantity($quantity);
        }

        if ($unitPrice <= 0) {
            throw InvalidInvoiceLines::invalidUnitPrice($unitPrice);
        }
    }

    /**
     * Calculate total price for this line (quantity × unit price).
     */
    public function calculateTotalPrice(): int
    {
        return $this->quantity * $this->unitPrice;
    }
}
