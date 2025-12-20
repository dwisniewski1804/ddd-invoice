<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use Modules\Invoices\Domain\Enums\StatusEnum;

/**
 * Exception thrown when an invoice cannot be marked as delivered.
 */
final class CannotMarkAsDelivered extends \DomainException
{
    public static function fromState(StatusEnum $currentStatus): self
    {
        return new self(
            message: sprintf(
                'Cannot mark invoice as sent-to-client. Invoice must be in sending state, but is currently %s.',
                $currentStatus->value
            )
        );
    }
}

