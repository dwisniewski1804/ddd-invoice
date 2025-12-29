<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Handler;

use Modules\Invoices\Application\Command\MarkInvoiceDelivered;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;

/**
 * Handler: Mark invoice as delivered.
 *
 * This handler:
 * 1. Loads invoice from repository
 * 2. Transitions invoice from "sending" to "sent-to-client" (via domain logic)
 * 3. Persists the updated invoice
 *
 * Idempotency: If invoice is not found or not in sending state,
 * the operation is silently ignored (protects against retries).
 */
final class MarkInvoiceDeliveredHandler
{
    public function __construct(
        private InvoiceRepository $repository,
    ) {}

    public function handle(MarkInvoiceDelivered $command): ?Invoice
    {
        $invoice = $this->repository->findById($command->invoiceId);

        if ($invoice === null) {
            // Invoice not found - this might be a delivery for a different resource type
            // We silently ignore it as the Notification module is shared
            return null;
        }

        try {
            // Business logic validation happens in the domain entity
            $invoice = $invoice->markAsSentToClient();
            $this->repository->save($invoice);

            return $invoice;
        } catch (\DomainException $e) {
            // Invoice not in sending state - idempotency: ignore
            return null;
        }
    }
}
