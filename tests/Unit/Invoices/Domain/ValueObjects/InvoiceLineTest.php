<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidInvoiceLines;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class InvoiceLineTest extends TestCase
{
    public function test_create_valid_invoice_line(): void
    {
        $line = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 5,
            unitPrice: 100,
        );

        $this->assertSame('Product 1', $line->name);
        $this->assertSame(5, $line->quantity);
        $this->assertSame(100, $line->unitPrice);
        $this->assertSame(500, $line->calculateTotalPrice());
    }

    public function test_cannot_create_invoice_line_with_zero_quantity(): void
    {
        $this->expectException(InvalidInvoiceLines::class);
        $this->expectExceptionMessage('Invoice line quantity must be greater than zero');

        new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 0,
            unitPrice: 100,
        );
    }

    public function test_cannot_create_invoice_line_with_negative_quantity(): void
    {
        $this->expectException(InvalidInvoiceLines::class);
        $this->expectExceptionMessage('Invoice line quantity must be greater than zero');

        new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: -1,
            unitPrice: 100,
        );
    }

    public function test_cannot_create_invoice_line_with_zero_unit_price(): void
    {
        $this->expectException(InvalidInvoiceLines::class);
        $this->expectExceptionMessage('Invoice line unit price must be greater than zero');

        new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 5,
            unitPrice: 0,
        );
    }

    public function test_cannot_create_invoice_line_with_negative_unit_price(): void
    {
        $this->expectException(InvalidInvoiceLines::class);
        $this->expectExceptionMessage('Invoice line unit price must be greater than zero');

        new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 5,
            unitPrice: -10,
        );
    }

    public function test_calculate_total_price(): void
    {
        $line = new InvoiceLine(
            id: Uuid::uuid4(),
            name: 'Product 1',
            quantity: 3,
            unitPrice: 150,
        );

        $this->assertSame(450, $line->calculateTotalPrice());
    }
}
