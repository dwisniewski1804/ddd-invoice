<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Handler;

use Modules\Invoices\Application\Command\SendInvoice;
use Modules\Invoices\Application\Handler\SendInvoiceHandler;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\CannotSendInvoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\Customer;
use Modules\Invoices\Domain\ValueObjects\Email;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class SendInvoiceHandlerTest extends TestCase
{
    private InvoiceRepository $repository;

    private NotificationFacadeInterface $notificationFacade;

    private SendInvoiceHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(InvoiceRepository::class);
        $this->notificationFacade = $this->createMock(NotificationFacadeInterface::class);
        $this->handler = new SendInvoiceHandler(
            repository: $this->repository,
            notificationFacade: $this->notificationFacade,
        );
    }

    /**
     * Given: A valid invoice in Draft status with product lines
     * When: Handler sends the invoice
     * Then: Status changes to Sending, NotificationFacade is called, and invoice is saved
     */
    public function test_send_invoice_successfully(): void
    {
        $invoiceId = Uuid::uuid4();
        $line = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 2,
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

        $command = new SendInvoice(invoiceId: $invoiceId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $this->notificationFacade->expects($this->once())
            ->method('notify')
            ->with($this->callback(function (NotifyData $data) use ($invoiceId) {
                return $data->resourceId->equals($invoiceId)
                    && $data->toEmail === 'john@example.com'
                    && str_contains($data->subject, $invoiceId->toString())
                    && str_contains($data->message, 'John Doe')
                    && str_contains($data->message, 'Product 1');
            }));

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Invoice $savedInvoice) use ($invoiceId) {
                return $savedInvoice->id->equals($invoiceId)
                    && $savedInvoice->status === StatusEnum::Sending;
            }));

        $result = $this->handler->handle($command);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertSame(StatusEnum::Sending, $result->status);
    }

    /**
     * Given: An invoice ID that does not exist in repository
     * When: Handler tries to send the invoice
     * Then: RuntimeException is thrown with appropriate message
     */
    public function test_send_invoice_throws_exception_when_invoice_not_found(): void
    {
        $invoiceId = Uuid::uuid4();
        $command = new SendInvoice(invoiceId: $invoiceId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn(null);

        $this->notificationFacade->expects($this->never())
            ->method('notify');

        $this->repository->expects($this->never())
            ->method('save');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Invoice not found: {$invoiceId->toString()}");

        $this->handler->handle($command);
    }

    /**
     * Given: An invoice in Draft status but without product lines
     * When: Handler tries to send the invoice
     * Then: CannotSendInvoice exception is thrown
     */
    public function test_send_invoice_throws_exception_when_invoice_cannot_be_sent(): void
    {
        $invoiceId = Uuid::uuid4();
        $invoice = Invoice::create(
            id: $invoiceId,
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
            lines: [], // Empty lines - cannot be sent
        );

        $command = new SendInvoice(invoiceId: $invoiceId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $this->notificationFacade->expects($this->never())
            ->method('notify');

        $this->repository->expects($this->never())
            ->method('save');

        $this->expectException(CannotSendInvoice::class);

        $this->handler->handle($command);
    }

    /**
     * Given: An invoice that is already in Sending status
     * When: Handler tries to send the invoice again
     * Then: CannotSendInvoice exception is thrown
     */
    public function test_send_invoice_throws_exception_when_invoice_not_in_draft_state(): void
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

        $command = new SendInvoice(invoiceId: $invoiceId);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with($invoiceId)
            ->willReturn($invoice);

        $this->notificationFacade->expects($this->never())
            ->method('notify');

        $this->repository->expects($this->never())
            ->method('save');

        $this->expectException(CannotSendInvoice::class);

        $this->handler->handle($command);
    }
}
