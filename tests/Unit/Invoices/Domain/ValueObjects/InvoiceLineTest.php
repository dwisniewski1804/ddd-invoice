<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidInvoiceLines;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class InvoiceLineTest extends TestCase
{
    public function testCreateValidInvoiceLine(): void
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

    public function testCannotCreateInvoiceLineWithZeroQuantity(): void
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

    public function testCannotCreateInvoiceLineWithNegativeQuantity(): void
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

    public function testCannotCreateInvoiceLineWithZeroUnitPrice(): void
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

    public function testCannotCreateInvoiceLineWithNegativeUnitPrice(): void
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

    public function testCalculateTotalPrice(): void
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

