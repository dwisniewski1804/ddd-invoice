<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceModel;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceProductLineModel;
use Modules\Invoices\Infrastructure\Persistence\Mapper\InvoiceMapper;
use Ramsey\Uuid\UuidInterface;

/**
 * Eloquent implementation of InvoiceRepository.
 *
 * This repository handles the persistence concerns, mapping between
 * domain entities and Eloquent models using the InvoiceMapper.
 */
final class EloquentInvoiceRepository implements InvoiceRepository
{
    public function __construct(
        private InvoiceMapper $mapper,
    ) {}

    public function save(Invoice $invoice): void
    {
        // Use transaction to ensure consistency
        DB::transaction(function () use ($invoice) {
            // Update or create the invoice
            InvoiceModel::updateOrCreate(
                ['id' => $invoice->id->toString()],
                $this->mapper->toPersistence($invoice)
            );

            // Delete existing product lines and recreate them
            InvoiceProductLineModel::where('invoice_id', $invoice->id->toString())->delete();

            foreach ($invoice->lines as $line) {
                InvoiceProductLineModel::create(
                    $this->mapper->lineToPersistence($line, $invoice->id->toString())
                );
            }
        });
    }

    public function findById(UuidInterface $id): ?Invoice
    {
        $model = InvoiceModel::with('productLines')->find($id->toString());

        if ($model === null) {
            return null;
        }

        return $this->mapper->toDomain($model);
    }

    public function findAll(): array
    {
        $models = InvoiceModel::with('productLines')->get();

        return $models->map(fn (InvoiceModel $model) => $this->mapper->toDomain($model))->toArray();
    }
}
