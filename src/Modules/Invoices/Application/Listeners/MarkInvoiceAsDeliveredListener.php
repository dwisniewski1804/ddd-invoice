<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Listeners;

use Modules\Invoices\Application\Command\MarkInvoiceDelivered;
use Modules\Invoices\Application\Handler\MarkInvoiceDeliveredHandler;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;

/**
 * Event listener: Handle ResourceDeliveredEvent.
 * 
 * When a notification is successfully delivered, this listener:
 * 1. Creates a MarkInvoiceDelivered command
 * 2. Delegates to MarkInvoiceDeliveredHandler
 * 
 * This implements the event-driven integration between Invoice and Notification modules.
 */
final class MarkInvoiceAsDeliveredListener
{
    public function __construct(
        private MarkInvoiceDeliveredHandler $handler,
    ) {}

    public function handle(ResourceDeliveredEvent $event): void
    {
        $this->handler->handle(new MarkInvoiceDelivered(
            invoiceId: $event->resourceId,
        ));
    }
}
