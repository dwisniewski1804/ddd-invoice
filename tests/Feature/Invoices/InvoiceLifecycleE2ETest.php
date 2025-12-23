<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-End test for the complete invoice lifecycle.
 * 
 * Tests the complete invoice workflow:
 * 1. Create invoice with product lines
 * 2. View invoice (verify draft status)
 * 3. Send invoice (verify sending status)
 * 4. Simulate delivery confirmation via webhook
 * 5. Verify sent-to-client status
 * 6. Verify total price calculation
 * 7. Test idempotency (duplicate webhook calls)
 * 8. Test error handling (sending already sent invoice)
 */
final class InvoiceLifecycleE2ETest extends TestCase
{
    use RefreshDatabase;

    public function testCompleteInvoiceLifecycle(): void
    {
        // Step 1: Create Invoice
        $createResponse = $this->postJson('/api/invoices', [
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
            'product_lines' => [
                [
                    'name' => 'Product A',
                    'quantity' => 2,
                    'unit_price' => 100,
                ],
                [
                    'name' => 'Product B',
                    'quantity' => 3,
                    'unit_price' => 50,
                ],
            ],
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonStructure([
                'invoice_id',
                'status',
                'customer_name',
                'customer_email',
                'product_lines',
                'total_price',
            ]);

        $invoiceId = $createResponse->json('invoice_id');
        $this->assertNotNull($invoiceId);

        // Step 2: View Invoice (should be in Draft status)
        $viewResponse = $this->getJson("/api/invoices/{$invoiceId}");

        $viewResponse->assertStatus(200)
            ->assertJson([
                'status' => 'draft',
                'invoice_id' => $invoiceId,
            ]);

        // Step 3: Send Invoice
        $sendResponse = $this->postJson("/api/invoices/{$invoiceId}/send");

        $sendResponse->assertStatus(200)
            ->assertJson([
                'status' => 'sending',
                'invoice_id' => $invoiceId,
            ]);

        // Step 4: Verify invoice is still in sending status
        $viewAfterSend = $this->getJson("/api/invoices/{$invoiceId}");

        $viewAfterSend->assertStatus(200)
            ->assertJson([
                'status' => 'sending',
                'invoice_id' => $invoiceId,
            ]);

        // Step 5: Simulate delivery confirmation via webhook
        $webhookResponse = $this->getJson("/api/notification/hook/delivered/{$invoiceId}");

        $webhookResponse->assertStatus(200);

        // Step 6: Verify invoice is now in sent-to-client status
        $finalView = $this->getJson("/api/invoices/{$invoiceId}");

        $finalView->assertStatus(200)
            ->assertJson([
                'status' => 'sent-to-client',
                'invoice_id' => $invoiceId,
            ]);

        // Step 7: Verify total price calculation
        $totalPrice = $finalView->json('total_price');
        $expectedTotal = (2 * 100) + (3 * 50); // Product A: 2*100 + Product B: 3*50 = 350

        $this->assertEquals($expectedTotal, $totalPrice, 'Total price should be calculated correctly');

        // Step 8: Test idempotency - call webhook again
        $webhookResponse2 = $this->getJson("/api/notification/hook/delivered/{$invoiceId}");
        $webhookResponse2->assertStatus(200);

        $finalView2 = $this->getJson("/api/invoices/{$invoiceId}");

        $finalView2->assertStatus(200)
            ->assertJson([
                'status' => 'sent-to-client',
                'invoice_id' => $invoiceId,
            ]);

        // Step 9: Test error case - try to send invoice that's already sent
        $errorResponse = $this->postJson("/api/invoices/{$invoiceId}/send");

        $errorResponse->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    public function testInvoiceCreationWithEmptyProductLines(): void
    {
        $createResponse = $this->postJson('/api/invoices', [
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane.doe@example.com',
            'product_lines' => [],
        ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'status' => 'draft',
            ]);

        $invoiceId = $createResponse->json('invoice_id');
        $this->assertNotNull($invoiceId);

        $viewResponse = $this->getJson("/api/invoices/{$invoiceId}");
        $viewResponse->assertStatus(200)
            ->assertJson([
                'status' => 'draft',
                'total_price' => 0,
            ]);
    }

    public function testCannotSendInvoiceWithoutProductLines(): void
    {
        $createResponse = $this->postJson('/api/invoices', [
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane.doe@example.com',
            'product_lines' => [],
        ]);

        $invoiceId = $createResponse->json('invoice_id');

        $sendResponse = $this->postJson("/api/invoices/{$invoiceId}/send");

        $sendResponse->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    public function testInvoiceNotFound(): void
    {
        $nonExistentId = '00000000-0000-0000-0000-000000000000';

        $viewResponse = $this->getJson("/api/invoices/{$nonExistentId}");
        $viewResponse->assertStatus(404)
            ->assertJsonStructure(['error']);
    }
}

