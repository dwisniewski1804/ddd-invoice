<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Handler;

use Modules\Invoices\Application\Command\CreateInvoice;
use Modules\Invoices\Application\Handler\CreateInvoiceHandler;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\Customer;
use Modules\Invoices\Domain\ValueObjects\Email;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class CreateInvoiceHandlerTest extends TestCase
{
    private InvoiceRepository $repository;
    private CreateInvoiceHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(InvoiceRepository::class);
        $this->handler = new CreateInvoiceHandler($this->repository);
    }

    /**
     * Given: A create invoice command with no product lines
     * When: Handler processes the command
     * Then: Invoice is created in Draft status and saved via repository
     */
    public function testCreateInvoiceWithoutLines(): void
    {
        $invoiceId = Uuid::uuid4();
        $customer = new Customer(
            name: 'John Doe',
            email: new Email('john@example.com'),
        );

        $command = new CreateInvoice(
            invoiceId: $invoiceId,
            customer: $customer,
            lines: [],
        );

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Invoice $invoice) use ($invoiceId, $customer) {
                return $invoice->id->equals($invoiceId)
                    && $invoice->customer->name === $customer->name
                    && $invoice->customer->email->value === $customer->email->value
                    && $invoice->status === StatusEnum::Draft
                    && empty($invoice->lines);
            }));

        $invoice = $this->handler->handle($command);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame(StatusEnum::Draft, $invoice->status);
        $this->assertEmpty($invoice->lines);
    }

    /**
     * Given: A create invoice command with multiple product lines
     * When: Handler processes the command
     * Then: Invoice is created with all lines and total price is calculated correctly
     */
    public function testCreateInvoiceWithLines(): void
    {
        $invoiceId = Uuid::uuid4();
        $customer = new Customer(
            name: 'Jane Smith',
            email: new Email('jane@example.com'),
        );

        $line1 = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 2,
            unitPrice: 100,
        );

        $line2 = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 2',
            quantity: 3,
            unitPrice: 50,
        );

        $command = new CreateInvoice(
            invoiceId: $invoiceId,
            customer: $customer,
            lines: [$line1, $line2],
        );

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Invoice $invoice) use ($invoiceId) {
                return $invoice->id->equals($invoiceId)
                    && $invoice->status === StatusEnum::Draft
                    && count($invoice->lines) === 2
                    && $invoice->calculateTotalPrice() === 350;
            }));

        $invoice = $this->handler->handle($command);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame(StatusEnum::Draft, $invoice->status);
        $this->assertCount(2, $invoice->lines);
        $this->assertSame(350, $invoice->calculateTotalPrice());
    }
}

