<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Handler;

use Modules\Invoices\Application\Command\CreateInvoice;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;

/**
 * Handler: Create a new invoice in draft state.
 * 
 * This handler:
 * - Creates invoice in draft state
 * - Persists invoice via repository
 */
final class CreateInvoiceHandler
{
    public function __construct(
        private InvoiceRepository $repository,
    ) {}

    public function handle(CreateInvoice $command): Invoice
    {
        $invoice = Invoice::create(
            id: $command->invoiceId,
            customer: $command->customer,
            lines: $command->lines,
        );

        $this->repository->save($invoice);

        return $invoice;
    }
}

