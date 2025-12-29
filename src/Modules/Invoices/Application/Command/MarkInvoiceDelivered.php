<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Command;

use Ramsey\Uuid\UuidInterface;

/**
 * Command: Mark invoice as delivered.
 */
final class MarkInvoiceDelivered
{
    public function __construct(
        public UuidInterface $invoiceId,
    ) {}
}
