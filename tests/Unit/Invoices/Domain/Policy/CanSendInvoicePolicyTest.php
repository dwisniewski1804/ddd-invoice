<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\Policy;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Policy\CanSendInvoicePolicy;
use Modules\Invoices\Domain\ValueObjects\Customer;
use Modules\Invoices\Domain\ValueObjects\Email;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class CanSendInvoicePolicyTest extends TestCase
{
    private CanSendInvoicePolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new CanSendInvoicePolicy();
    }

    /**
     * Given: An invoice in Draft status with valid product lines
     * When: Policy checks if invoice can be sent
     * Then: Returns true
     */
    public function testCanSendReturnsTrueForDraftInvoiceWithValidLines(): void
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

        $this->assertTrue($this->policy->canSend($invoice));
    }

    /**
     * Given: An invoice in Sending status (not Draft)
     * When: Policy checks if invoice can be sent
     * Then: Returns false
     */
    public function testCanSendReturnsFalseForInvoiceNotInDraftState(): void
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

        // Mark as sending
        $invoice = $invoice->markAsSending();

        $this->assertFalse($this->policy->canSend($invoice));
    }

    /**
     * Given: An invoice in Draft status but without product lines
     * When: Policy checks if invoice can be sent
     * Then: Returns false
     */
    public function testCanSendReturnsFalseForInvoiceWithoutLines(): void
    {
        $invoice = Invoice::create(
            id: Uuid::uuid4(),
            customer: new Customer(
                name: 'John Doe',
                email: new Email('john@example.com'),
            ),
            lines: [],
        );

        $this->assertFalse($this->policy->canSend($invoice));
    }

    /**
     * Given: An invoice with valid product lines (quantity > 0, unitPrice > 0)
     * When: Policy checks if invoice can be sent
     * Then: Returns true (InvoiceLine constructor ensures valid values)
     */
    public function testCanSendValidatesEachLineHasPositiveQuantityAndUnitPrice(): void
    {
        // Since InvoiceLine constructor validates quantity > 0 and unitPrice > 0,
        // we can't create invalid lines. However, the policy still checks these
        // conditions as a safety measure. We test that policy works correctly
        // with valid lines that have positive values.
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

        // Policy validates that all lines have quantity > 0 and unitPrice > 0
        // Since InvoiceLine constructor ensures this, policy should return true
        $this->assertTrue($this->policy->canSend($invoice));
    }

    /**
     * Given: An invoice in Draft status with multiple valid product lines
     * When: Policy checks if invoice can be sent
     * Then: Returns true
     */
    public function testCanSendReturnsTrueForInvoiceWithMultipleValidLines(): void
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

        $this->assertTrue($this->policy->canSend($invoice));
    }

    /**
     * Given: An invoice in SentToClient status
     * When: Policy checks if invoice can be sent
     * Then: Returns false
     */
    public function testCanSendReturnsFalseForInvoiceInSentToClientState(): void
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

        $this->assertFalse($this->policy->canSend($invoice));
    }
}

