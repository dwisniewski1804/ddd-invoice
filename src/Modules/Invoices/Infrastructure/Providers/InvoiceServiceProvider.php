<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Infrastructure\Persistence\EloquentInvoiceRepository;
use Modules\Invoices\Infrastructure\Persistence\Mapper\InvoiceMapper;

/**
 * Service provider for Invoice module.
 * 
 * Registers:
 * - Repository bindings
 * - Mapper bindings
 */
final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register mapper
        $this->app->singleton(InvoiceMapper::class);

        // Register repository
        $this->app->bind(
            InvoiceRepository::class,
            EloquentInvoiceRepository::class
        );
    }
}
