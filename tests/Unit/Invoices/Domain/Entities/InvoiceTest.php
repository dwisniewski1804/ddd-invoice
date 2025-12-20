<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\CannotMarkAsDelivered;
use Modules\Invoices\Domain\Exceptions\CannotSendInvoice;
use Modules\Invoices\Domain\ValueObjects\Customer;
use Modules\Invoices\Domain\ValueObjects\Email;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class InvoiceTest extends TestCase
{
    public function testCreateInvoiceInDraftState(): void
    {
        $invoice = Invoice::create(
            id: Uuid::uuid4(),
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
        );

        $this->assertSame(StatusEnum::Draft, $invoice->status);
        $this->assertSame('John Doe', $invoice->customer->name);
        $this->assertSame('john@example.com', $invoice->customer->email->value);
        $this->assertEmpty($invoice->lines);
    }

    public function testCreateInvoiceWithLines(): void
    {
        $line = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 2,
            unitPrice: 100,
        );

        $invoice = Invoice::create(
            id: Uuid::uuid4(),
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
            lines: [$line],
        );

        $this->assertCount(1, $invoice->lines);
        $this->assertSame(200, $invoice->calculateTotalPrice());
    }

    public function testMarkAsSendingFromDraftState(): void
    {
        $line = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 1,
            unitPrice: 100,
        );

        $invoice = Invoice::create(
            id: Uuid::uuid4(),
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
            lines: [$line],
        );

        $updatedInvoice = $invoice->markAsSending();

        $this->assertSame(StatusEnum::Sending, $updatedInvoice->status);
    }

    public function testCannotMarkAsSendingFromNonDraftState(): void
    {
        $line = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 1,
            unitPrice: 100,
        );

        $invoice = Invoice::create(
            id: Uuid::uuid4(),
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
            lines: [$line],
        );

        $invoice = $invoice->markAsSending();

        $this->expectException(CannotSendInvoice::class);
        $invoice->markAsSending();
    }

    public function testCannotMarkAsSendingWithoutValidLines(): void
    {
        $invoice = Invoice::create(
            id: Uuid::uuid4(),
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
        );

        $this->expectException(CannotSendInvoice::class);
        $this->expectExceptionMessage('Cannot send invoice. Invoice must have at least one line');
        
        $invoice->markAsSending();
    }

    public function testMarkAsSentToClientFromSendingState(): void
    {
        $line = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 1,
            unitPrice: 100,
        );

        $invoice = Invoice::create(
            id: Uuid::uuid4(),
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
            lines: [$line],
        );

        $invoice = $invoice->markAsSending();
        $invoice = $invoice->markAsSentToClient();

        $this->assertSame(StatusEnum::SentToClient, $invoice->status);
    }

    public function testCannotMarkAsSentToClientFromNonSendingState(): void
    {
        $invoice = Invoice::create(
            id: Uuid::uuid4(),
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
        );

        $this->expectException(CannotMarkAsDelivered::class);
        $this->expectExceptionMessage('Cannot mark invoice as sent-to-client');
        
        $invoice->markAsSentToClient();
    }

    public function testCalculateTotalPrice(): void
    {
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

        $invoice = Invoice::create(
            id: Uuid::uuid4(),
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
            lines: [$line1, $line2],
        );

        $this->assertSame(350, $invoice->calculateTotalPrice()); // (2 * 100) + (3 * 50) = 350
    }

    public function testCalculateTotalPriceWithEmptyLines(): void
    {
        $invoice = Invoice::create(
            id: Uuid::uuid4(),
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
        );

        $this->assertSame(0, $invoice->calculateTotalPrice());
    }
}
