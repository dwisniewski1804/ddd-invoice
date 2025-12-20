<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\DTO;

use Modules\Invoices\Domain\Entities\Invoice;

/**
 * DTO for invoice view representation.
 * 
 * Transforms domain entity to view format.
 */
final class InvoiceView
{
    /**
     * @param array{id: string, name: string, quantity: int, unit_price: int, total_unit_price: int}[] $lines
     */
    public function __construct(
        public string $invoice_id,
        public string $status,
        public string $customer_name,
        public string $customer_email,
        public array $lines,
        public int $total_price,
    ) {}

    public static function fromDomain(Invoice $invoice): self
    {
        $lines = [];
        foreach ($invoice->lines as $line) {
            $lines[] = [
                'id' => $line->id->toString(),
                'name' => $line->name,
                'quantity' => $line->quantity,
                'unit_price' => $line->unitPrice,
                'total_unit_price' => $line->calculateTotalPrice(),
            ];
        }

        return new self(
            invoice_id: $invoice->id->toString(),
            status: $invoice->status->value,
            customer_name: $invoice->customer->name,
            customer_email: $invoice->customer->email->value,
            lines: $lines,
            total_price: $invoice->calculateTotalPrice(),
        );
    }

    /**
     * Convert to array for JSON response.
     * 
     * @return array{invoice_id: string, status: string, customer_name: string, customer_email: string, product_lines: array, total_price: int}
     */
    public function toArray(): array
    {
        return [
            'invoice_id' => $this->invoice_id,
            'status' => $this->status,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'product_lines' => $this->lines,
            'total_price' => $this->total_price,
        ];
    }
}

