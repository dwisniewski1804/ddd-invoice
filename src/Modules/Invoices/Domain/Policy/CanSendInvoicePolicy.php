<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Policy;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;

/**
 * Policy: Can this invoice be sent?
 *
 * Encapsulates the business rule for determining if an invoice
 * can be sent. This policy improves readability and reusability.
 *
 * Rules:
 * - Invoice must be in draft state
 * - Invoice must have at least one line
 * - Each line must have quantity > 0 and unitPrice > 0
 */
final class CanSendInvoicePolicy
{
    public function canSend(Invoice $invoice): bool
    {
        // Check status
        if ($invoice->status !== StatusEnum::Draft) {
            return false;
        }

        // Check presence of lines
        if (empty($invoice->lines)) {
            return false;
        }

        // Validate each line
        foreach ($invoice->lines as $line) {
            if ($line->quantity <= 0 || $line->unitPrice <= 0) {
                return false;
            }
        }

        return true;
    }
}
