# ADR-0004 — Bidirectional Context Communication: Command Out, Integration Event In

- Status: Accepted
- Date: 2025-12-20

## Context
There are two bounded contexts:
- Invoice (our core domain)
- Notification (external/upstream)

The communication is bidirectional:
- Invoice requests sending an email
- Notification later confirms delivery

These are not the same kind of message.

## Decision
Use two distinct interaction types:
1) Invoice → Notification: Command/Request via `NotificationFacade`
   - expresses intent (“send email”)
2) Notification → Invoice: Integration Event (`ResourceDeliveredEvent`) via webhook/listener
   - expresses fact (“delivered”)

Do not share domain models between contexts. Translate external payloads at the boundary (ACL).

## Consequences
### Positive
- Clear semantics: commands vs events.
- Loose coupling between bounded contexts.
- Domain does not depend on external payload formats.

### Negative
- Requires boundary translation (webhook DTO → invoiceId).
- Requires idempotent handling due to at-least-once delivery.

## Alternatives Considered
1. Treat both directions as events
   - Blurs semantics and complicates intent vs fact separation.
2. Direct synchronous call for delivery confirmation
   - Unrealistic; delivery is naturally asynchronous.
