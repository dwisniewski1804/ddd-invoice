<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Invoices\Application\Command\CreateInvoice;
use Modules\Invoices\Application\Command\SendInvoice;
use Modules\Invoices\Application\DTO\InvoiceView;
use Modules\Invoices\Application\Handler\CreateInvoiceHandler;
use Modules\Invoices\Application\Handler\SendInvoiceHandler;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\Customer;
use Modules\Invoices\Domain\ValueObjects\Email;
use Modules\Invoices\Domain\ValueObjects\InvoiceLine;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP controller for invoice operations.
 *
 * Thin controller that delegates to handlers.
 * Handles HTTP concerns (request/response transformation).
 */
final class InvoiceController
{
    public function __construct(
        private CreateInvoiceHandler $createHandler,
        private SendInvoiceHandler $sendHandler,
        private InvoiceRepository $repository,
    ) {}

    /**
     * Create a new invoice.
     */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'product_lines' => 'sometimes|array',
            'product_lines.*.name' => 'required_with:product_lines|string|max:255',
            'product_lines.*.quantity' => 'required_with:product_lines|integer|min:1',
            'product_lines.*.unit_price' => 'required_with:product_lines|integer|min:1',
        ]);

        $invoiceId = Uuid::uuid4();
        $lines = [];

        if (isset($data['product_lines'])) {
            foreach ($data['product_lines'] as $lineData) {
                $lines[] = new InvoiceLine(
                    id: Uuid::uuid4(),
                    name: $lineData['name'],
                    quantity: $lineData['quantity'],
                    unitPrice: $lineData['unit_price'],
                );
            }
        }

        $invoice = $this->createHandler->handle(new CreateInvoice(
            invoiceId: $invoiceId,
            customer: new Customer(
                name: $data['customer_name'],
                email: new Email($data['customer_email']),
            ),
            lines: $lines,
        ));

        return new JsonResponse(
            data: InvoiceView::fromDomain($invoice)->toArray(),
            status: Response::HTTP_CREATED
        );
    }

    /**
     * View invoice details.
     */
    public function view(string $id): JsonResponse
    {
        $invoiceId = Uuid::fromString($id);
        $invoice = $this->repository->findById($invoiceId);

        if ($invoice === null) {
            return new JsonResponse(
                data: ['error' => 'Invoice not found'],
                status: Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse(
            data: InvoiceView::fromDomain($invoice)->toArray()
        );
    }

    /**
     * Send invoice to customer.
     */
    public function send(string $id): JsonResponse
    {
        $invoiceId = Uuid::fromString($id);

        try {
            $invoice = $this->sendHandler->handle(new SendInvoice(
                invoiceId: $invoiceId,
            ));

            return new JsonResponse(
                data: InvoiceView::fromDomain($invoice)->toArray()
            );
        } catch (\DomainException $e) {
            return new JsonResponse(
                data: ['error' => $e->getMessage()],
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\RuntimeException $e) {
            return new JsonResponse(
                data: ['error' => $e->getMessage()],
                status: Response::HTTP_NOT_FOUND
            );
        }
    }
}
