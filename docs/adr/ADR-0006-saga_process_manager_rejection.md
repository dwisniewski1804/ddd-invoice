# ADR-0006 — No Saga / Process Manager for the Current Workflow

- Status: Accepted
- Date: 2025-12-20

## Context
The workflow is simple:
- send invoice (draft → sending)
- mark delivered (sending → sent-to-client)

There is no multi-step branching, no compensation logic, and no long-running orchestration beyond one integration event.

## Decision
Do not implement a Saga / Process Manager.
Use:
- a use-case handler for sending
- a single event handler for delivery confirmation

## Consequences
### Positive
- Keeps the solution proportional and easy to reason about.
- Avoids additional state machines and persistence for saga state.

### Negative
- If the workflow expands (payments, retries, cancellation), a saga may become appropriate later.

## Alternatives Considered
1. Introduce a Saga for send/deliver
   - Adds complexity without meaningful benefit for this task.
