<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use Modules\Invoices\Domain\Enums\StatusEnum;

/**
 * Exception thrown when an invoice cannot be sent.
 */
final class CannotSendInvoice extends \DomainException
{
    public static function fromState(StatusEnum $currentStatus): self
    {
        return new self(
            message: sprintf(
                'Cannot send invoice. Invoice must be in draft state, but is currently %s.',
                $currentStatus->value
            )
        );
    }

    public static function withoutValidLines(): self
    {
        return new self(
            message: 'Cannot send invoice. Invoice must have at least one line with quantity and unit price greater than zero.'
        );
    }
}
