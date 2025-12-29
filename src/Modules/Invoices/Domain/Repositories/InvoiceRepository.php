<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Repositories;

use Modules\Invoices\Domain\Entities\Invoice;
use Ramsey\Uuid\UuidInterface;

/**
 * Repository interface for invoice persistence.
 *
 * This interface belongs to the domain layer and defines
 * the contract for persisting and retrieving invoices.
 */
interface InvoiceRepository
{
    public function save(Invoice $invoice): void;

    public function findById(UuidInterface $id): ?Invoice;

    /**
     * @return Invoice[]
     */
    public function findAll(): array;
}
