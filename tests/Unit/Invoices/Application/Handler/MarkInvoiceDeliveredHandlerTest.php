<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Handler;

use Modules\Invoices\Application\Command\MarkInvoiceDelivered;
use Modules\Invoices\Application\Handler\MarkInvoiceDeliveredHandler;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\Customer;
use Modules\Invoices\Domain\ValueObjects\Email;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class MarkInvoiceDeliveredHandlerTest extends TestCase
{
    private InvoiceRepository $repository;
    private MarkInvoiceDeliveredHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(InvoiceRepository::class);
        $this->handler = new MarkInvoiceDeliveredHandler($this->repository);
    }

    /**
     * Given: An invoice in Sending status
     * When: Handler marks invoice as delivered
     * Then: Status changes to SentToClient and invoice is saved
     */
    public function testMarkInvoiceAsDeliveredSuccessfully(): void
    {
        $invoiceId = Uuid::uuid4();
        $line = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 1,
            unitPrice: 100,
        );

        $invoice = Invoice::create(
            id: $invoiceId,
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
            lines: [$line],
        );

        // Mark as sending first
        $invoice = $invoice->markAsSending();

        $command = new MarkInvoiceDelivered(invoiceId: $invoiceId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Invoice $savedInvoice) use ($invoiceId) {
                return $savedInvoice->id->equals($invoiceId)
                    && $savedInvoice->status === StatusEnum::SentToClient;
            }));

        $result = $this->handler->handle($command);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertSame(StatusEnum::SentToClient, $result->status);
    }

    /**
     * Given: An invoice ID that does not exist in repository
     * When: Handler tries to mark invoice as delivered
     * Then: Returns null (idempotency - silent ignore for non-existent invoice)
     */
    public function testMarkInvoiceAsDeliveredReturnsNullWhenInvoiceNotFound(): void
    {
        $invoiceId = Uuid::uuid4();
        $command = new MarkInvoiceDelivered(invoiceId: $invoiceId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method('save');

        $result = $this->handler->handle($command);

        $this->assertNull($result);
    }

    /**
     * Given: An invoice in Draft status (not Sending)
     * When: Handler tries to mark invoice as delivered
     * Then: Returns null (idempotency - silent ignore for wrong status)
     */
    public function testMarkInvoiceAsDeliveredReturnsNullWhenInvoiceNotInSendingState(): void
    {
        $invoiceId = Uuid::uuid4();
        $line = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 1,
            unitPrice: 100,
        );

        // Invoice in Draft state
        $invoice = Invoice::create(
            id: $invoiceId,
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
            lines: [$line],
        );

        $command = new MarkInvoiceDelivered(invoiceId: $invoiceId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        // Should not save because invoice is not in sending state
        $this->repository->expects($this->never())
            ->method('save');

        $result = $this->handler->handle($command);

        // Idempotency: silently ignore
        $this->assertNull($result);
    }

    /**
     * Given: An invoice already in SentToClient status
     * When: Handler tries to mark invoice as delivered again
     * Then: Returns null (idempotency - protection against duplicate deliveries)
     */
    public function testMarkInvoiceAsDeliveredReturnsNullWhenInvoiceAlreadyDelivered(): void
    {
        $invoiceId = Uuid::uuid4();
        $line = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 1,
            unitPrice: 100,
        );

        $invoice = Invoice::create(
            id: $invoiceId,
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
            lines: [$line],
        );

        // Mark as sent to client
        $invoice = $invoice->markAsSending();
        $invoice = $invoice->markAsSentToClient();

        $command = new MarkInvoiceDelivered(invoiceId: $invoiceId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        // Should not save because invoice is already delivered
        $this->repository->expects($this->never())
            ->method('save');

        $result = $this->handler->handle($command);

        // Idempotency: silently ignore duplicate delivery
        $this->assertNull($result);
    }
}

