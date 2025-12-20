<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Command;

use Modules\Invoices\Domain\ValueObjects\Customer;
use Modules\Invoices\Domain\ValueObjects\Email;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use Ramsey\Uuid\UuidInterface;

/**
 * Command: Create a new invoice.
 */
final class CreateInvoice
{
    /**
     * @param InvoiceLine[] $lines
     */
    public function __construct(
        public UuidInterface $invoiceId,
        public Customer $customer,
        public array $lines = [],
    ) {}
}

