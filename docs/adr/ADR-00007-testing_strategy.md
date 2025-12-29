# ADR-0007 — Testing Strategy: Domain-First Unit Tests + Minimal HTTP Smoke Tests

- Status: Accepted
- Date: 2025-12-20

## Context
Task requirements emphasize that core invoice logic should be unit tested.
Testing returned values from endpoints is not required.

## Decision
- Primary focus: unit tests for domain invariants and transitions.
- Secondary: unit tests for application handlers with fakes/spies.
- Optional: minimal feature/smoke tests for HTTP routes (status codes only).

## Consequences
### Positive
- Fast tests with high signal.
- Confidence in business correctness.
- Avoids testing framework internals.

### Negative
- Less coverage of serialization and endpoint payload formatting (acceptable for scope).

## Alternatives Considered
1. Heavy feature testing of controllers and JSON responses
   - Slower and mainly tests framework behavior rather than business logic.
