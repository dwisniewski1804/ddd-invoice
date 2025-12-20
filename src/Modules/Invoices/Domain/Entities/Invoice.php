<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\CannotMarkAsDelivered;
use Modules\Invoices\Domain\Exceptions\CannotSendInvoice;
use Modules\Invoices\Domain\Policy\CanSendInvoicePolicy;
use Modules\Invoices\Domain\ValueObjects\Customer;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use Ramsey\Uuid\UuidInterface;

/**
 * Invoice aggregate root.
 * 
 * Encapsulates business rules around invoice lifecycle:
 * - Invoices are created in draft state
 * - Can only be sent from draft state
 * - Must have valid product lines to be sent
 * - Can only transition to sent-to-client from sending state
 */
final class Invoice
{
    /**
     * @param InvoiceLine[] $lines
     */
    public function __construct(
        public UuidInterface $id,
        public Customer $customer,
        public StatusEnum $status,
        public array $lines,
    ) {}

    /**
     * Create a new invoice in draft state.
     * 
     * @param InvoiceLine[] $lines
     */
    public static function create(
        UuidInterface $id,
        Customer $customer,
        array $lines = [],
    ): self {
        return new self(
            id: $id,
            customer: $customer,
            status: StatusEnum::Draft,
            lines: $lines,
        );
    }

    /**
     * Mark invoice as sending.
     * 
     * Business rule: Invoice can only be sent if it's in draft state
     * and has valid product lines (quantity and price > 0).
     * 
     * Uses CanSendInvoicePolicy to check if invoice can be sent.
     * 
     * @throws CannotSendInvoice
     */
    public function markAsSending(): self
    {
        $policy = new CanSendInvoicePolicy();
        
        if (!$policy->canSend($this)) {
            if ($this->status !== StatusEnum::Draft) {
                throw CannotSendInvoice::fromState($this->status);
            }
            throw CannotSendInvoice::withoutValidLines();
        }

        return new self(
            id: $this->id,
            customer: $this->customer,
            status: StatusEnum::Sending,
            lines: $this->lines,
        );
    }

    /**
     * Mark invoice as sent to client.
     * 
     * Business rule: Invoice can only transition to sent-to-client from sending state.
     * This is typically triggered by the ResourceDeliveredEvent.
     * 
     * @throws CannotMarkAsDelivered
     */
    public function markAsSentToClient(): self
    {
        if ($this->status !== StatusEnum::Sending) {
            throw CannotMarkAsDelivered::fromState($this->status);
        }

        return new self(
            id: $this->id,
            customer: $this->customer,
            status: StatusEnum::SentToClient,
            lines: $this->lines,
        );
    }

    /**
     * Calculate total price of the invoice.
     */
    public function calculateTotalPrice(): int
    {
        $total = 0;
        foreach ($this->lines as $line) {
            $total += $line->calculateTotalPrice();
        }
        return $total;
    }
}
