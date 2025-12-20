<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence\Mapper;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\ValueObjects\Customer;
use Modules\Invoices\Domain\ValueObjects\Email;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceModel;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceProductLineModel;
use Ramsey\Uuid\Uuid;

/**
 * Mapper between domain entities and Eloquent models.
 * 
 * This mapper handles the translation between:
 * - Domain (Invoice, InvoiceLine, Customer, Email)
 * - Persistence (InvoiceModel, InvoiceProductLineModel)
 * 
 * The repository is the only place that uses this mapper.
 */
final class InvoiceMapper
{
    /**
     * Convert Eloquent model to domain entity.
     */
    public function toDomain(InvoiceModel $model): Invoice
    {
        $lines = [];
        foreach ($model->productLines as $lineModel) {
            $lines[] = new InvoiceLine(
                id: Uuid::fromString($lineModel->id),
                name: $lineModel->name,
                quantity: $lineModel->quantity,
                unitPrice: $lineModel->price,
            );
        }

        return new Invoice(
            id: Uuid::fromString($model->id),
            customer: new Customer(
                name: $model->customer_name,
                email: new Email($model->customer_email),
            ),
            status: StatusEnum::from($model->status),
            lines: $lines,
        );
    }

    /**
     * Convert domain entity to Eloquent model data.
     * 
     * @return array{id: string, customer_name: string, customer_email: string, status: string}
     */
    public function toPersistence(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id->toString(),
            'customer_name' => $invoice->customer->name,
            'customer_email' => $invoice->customer->email->value,
            'status' => $invoice->status->value,
        ];
    }

    /**
     * Convert domain invoice line to persistence data.
     * 
     * @return array{id: string, invoice_id: string, name: string, price: int, quantity: int}
     */
    public function lineToPersistence(InvoiceLine $line, string $invoiceId): array
    {
        return [
            'id' => $line->id->toString(),
            'invoice_id' => $invoiceId,
            'name' => $line->name,
            'price' => $line->unitPrice,
            'quantity' => $line->quantity,
        ];
    }
}

