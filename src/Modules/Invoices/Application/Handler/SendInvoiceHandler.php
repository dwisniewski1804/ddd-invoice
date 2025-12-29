<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Handler;

use Modules\Invoices\Application\Command\SendInvoice;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;

/**
 * Handler: Send invoice to customer.
 *
 * This handler:
 * 1. Loads invoice from repository
 * 2. Validates invoice can be sent (via domain logic)
 * 3. Sends notification via NotificationFacade
 * 4. Updates invoice status to "sending"
 * 5. Persists the updated invoice
 *
 * The actual delivery confirmation happens asynchronously via ResourceDeliveredEvent.
 */
final class SendInvoiceHandler
{
    public function __construct(
        private InvoiceRepository $repository,
        private NotificationFacadeInterface $notificationFacade,
    ) {}

    public function handle(SendInvoice $command): Invoice
    {
        $invoice = $this->repository->findById($command->invoiceId);

        if ($invoice === null) {
            throw new \RuntimeException("Invoice not found: {$command->invoiceId->toString()}");
        }

        // Business logic validation happens in the domain entity
        $invoice = $invoice->markAsSending();

        // Send notification
        $this->notificationFacade->notify(new NotifyData(
            resourceId: $invoice->id,
            toEmail: $invoice->customer->email->value,
            subject: 'Invoice #'.$invoice->id->toString(),
            message: $this->buildInvoiceMessage($invoice),
        ));

        // Persist the updated invoice
        $this->repository->save($invoice);

        return $invoice;
    }

    private function buildInvoiceMessage(Invoice $invoice): string
    {
        $message = "Dear {$invoice->customer->name},\n\n";
        $message .= "Please find your invoice details below:\n\n";
        $message .= "Invoice ID: {$invoice->id->toString()}\n";
        $message .= "Status: {$invoice->status->value}\n\n";
        $message .= "Product Lines:\n";

        foreach ($invoice->lines as $line) {
            $lineTotal = $line->calculateTotalPrice();
            $message .= "- {$line->name}: {$line->quantity} × {$line->unitPrice} = {$lineTotal}\n";
        }

        $message .= "\nTotal: {$invoice->calculateTotalPrice()}\n";

        return $message;
    }
}
