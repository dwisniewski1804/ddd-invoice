# ADR-0005 — Webhook/Event Handlers Must Be Idempotent

- Status: Accepted
- Date: 2025-12-20

## Context
Webhook delivery is typically “at-least-once”:
- duplicate deliveries are possible
- events can arrive late or out of order

Updating invoice status must not break the system under retries.

## Decision
Webhook handler / event listener behavior:
- if invoice does not exist → ignore (log)
- if invoice status is not `sending` → ignore (log)
- only transition `sending → sent-to-client`

## Consequences
### Positive
- Robust against retries and duplicates.
- Safe under eventual consistency assumptions.
- Avoids noisy errors for expected webhook behavior.

### Negative
- Requires good logging/observability to diagnose unexpected flows.
- “Ignore” strategy must be intentional and documented.

## Alternatives Considered
1. Throw errors for unexpected state
   - Can cause repeated webhook retries and operational noise.
2. Persist a delivery-event table for strict deduplication
   - Possibly useful later, but overkill for this task scope.
