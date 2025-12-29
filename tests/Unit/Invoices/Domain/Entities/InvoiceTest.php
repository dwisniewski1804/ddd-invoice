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
    public function test_create_invoice_in_draft_state(): void
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

    public function test_create_invoice_with_lines(): void
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

    public function test_mark_as_sending_from_draft_state(): void
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

    public function test_cannot_mark_as_sending_from_non_draft_state(): void
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

    public function test_cannot_mark_as_sending_without_valid_lines(): void
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

    public function test_mark_as_sent_to_client_from_sending_state(): void
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

    public function test_cannot_mark_as_sent_to_client_from_non_sending_state(): void
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

    public function test_calculate_total_price(): void
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

    public function test_calculate_total_price_with_empty_lines(): void
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
