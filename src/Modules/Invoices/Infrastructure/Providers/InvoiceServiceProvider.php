<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Application\Listeners\MarkInvoiceAsDeliveredListener;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Infrastructure\Persistence\EloquentInvoiceRepository;
use Modules\Invoices\Infrastructure\Persistence\Mapper\InvoiceMapper;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;

/**
 * Service provider for Invoice module.
 * 
 * Registers:
 * - Repository bindings
 * - Mapper bindings
 * - Event listeners
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

    public function boot(Dispatcher $dispatcher): void
    {
        // Register event listener for delivery confirmation
        $dispatcher->listen(
            ResourceDeliveredEvent::class,
            MarkInvoiceAsDeliveredListener::class
        );
    }
}
