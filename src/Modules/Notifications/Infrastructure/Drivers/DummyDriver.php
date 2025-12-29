<?php

declare(strict_types=1);

namespace Modules\Notifications\Infrastructure\Drivers;

use Modules\Notifications\Application\Services\NotificationService;

class DummyDriver implements DriverInterface
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function send(
        string $toEmail,
        string $subject,
        string $message,
        string $reference,
    ): bool {
        // Simulate successful delivery
        // In real implementation, this would call an external service

        // After successful delivery, notify via NotificationService
        $this->notificationService->delivered($reference);

        return true;
    }
}
