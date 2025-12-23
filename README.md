## Invoice Structure:

The invoice should contain the following fields:
* **Invoice ID**: Auto-generated during creation.
* **Invoice Status**: Possible states include `draft,` `sending,` and `sent-to-client`.
* **Customer Name** 
* **Customer Email** 
* **Invoice Product Lines**, each with:
  * **Product Name**
  * **Quantity**: Integer, must be positive. 
  * **Unit Price**: Integer, must be positive.
  * **Total Unit Price**: Calculated as Quantity x Unit Price. 
* **Total Price**: Sum of all Total Unit Prices.

## Required Endpoints:

1. **View Invoice**: Retrieve invoice data in the format above.
2. **Create Invoice**: Initialize a new invoice.
3. **Send Invoice**: Handle the sending of an invoice.

## Functional Requirements:

### Invoice Criteria:

* An invoice can only be created in `draft` status. 
* An invoice can be created with empty product lines. 
* An invoice can only be sent if it is in `draft` status. 
* An invoice can only be marked as `sent-to-client` if its current status is `sending`. 
* To be sent, an invoice must contain product lines with both quantity and unit price as positive integers greater than **zero**.

### Invoice Sending Workflow:

* **Send an email notification** to the customer using the `NotificationFacade`. 
  * The email's subject and message may be hardcoded or customized as needed. 
  * Change the **Invoice Status** to `sending` after sending the notification.

### Delivery:

* Upon successful delivery by the Dummy notification provider:
  * The **Notification Module** triggers a `ResourceDeliveredEvent` via webhook.
  * The **Invoice Module** listens for and captures this event.
  * The **Invoice Status** is updated from `sending` to `sent-to-client`.
  * **Note**: This transition requires that the invoice is currently in the `sending` status.

## Technical Requirements:

* **Preferred Approach**: Domain-Driven Design (DDD) is preferred for this project. If you have experience with DDD, please feel free to apply this methodology. However, if you are more comfortable with another approach, you may choose an alternative structure.
* **Alternative Submission**: If you have a different, comparable project or task that showcases your skills, you may submit that instead of creating this task.
* **Unit Tests**: Core invoice logic should be unit tested. Testing the returned values from endpoints is not required.
* **Documentation**: Candidates are encouraged to document their decisions and reasoning in comments or a README file, explaining why specific implementations or structures were chosen.

## Architecture Decision Records (ADRs)
See `docs/adr/` for decisions and trade-offs.


## Setup Instructions:

* Start the project by running `./start.sh`.
* To access the container environment, use: `docker compose exec app bash`.

## Testing:

### Unit Tests:
```bash
# Run all tests (Unit + Feature)
docker compose exec app php artisan test

# Run only Unit tests
docker compose exec app php artisan test --testsuite=Unit

# Run specific unit test file
docker compose exec app php artisan test tests/Unit/Invoices/Domain/Entities/InvoiceTest.php

# Run specific test method
docker compose exec app php artisan test --filter testMethodName

# Run tests with coverage (requires Xdebug)
docker compose exec app php artisan test --coverage
```

### Feature/E2E Tests:
```bash
# Run all tests (Unit + Feature)
docker compose exec app php artisan test

# Run only Feature tests
docker compose exec app php artisan test --testsuite=Feature

# Run specific E2E test
docker compose exec app php artisan test tests/Feature/Invoices/InvoiceLifecycleE2ETest.php
```

The E2E tests (`tests/Feature/Invoices/InvoiceLifecycleE2ETest.php`) verify the complete invoice lifecycle:
- Invoice creation with product lines
- Status transitions (draft → sending → sent-to-client)
- Total price calculation
- Webhook delivery confirmation
- Idempotency handling
- Error handling
- Edge cases (empty product lines, invalid operations)

These tests use Laravel's built-in testing framework with PHPUnit, providing proper test isolation, database transactions, and integration with the application's service container.
