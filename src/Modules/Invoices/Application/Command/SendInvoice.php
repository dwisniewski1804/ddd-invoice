<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Command;

use Ramsey\Uuid\UuidInterface;

/**
 * Command: Send invoice to customer.
 */
final class SendInvoice
{
    public function __construct(
        public UuidInterface $invoiceId,
    ) {}
}
