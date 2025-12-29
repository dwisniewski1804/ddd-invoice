# ADR-0002 — Keep Domain Entities Persistence-Ignorant (No ORM Annotations, No save())

- Status: Accepted
- Date: 2025-12-20

## Context
In strict DDD, domain entities should not depend on persistence mechanisms (ORM, database schema, Active Record APIs).
Laravel’s Eloquent encourages placing persistence operations on models (`save()`, `delete()`).

## Decision
Domain entities (e.g., Invoice) must:
- have no ORM annotations or database mapping
- have no persistence methods (`save()`, `delete()`, `find()`)

Persistence is handled via:
- Eloquent models in Infrastructure
- Repository + Mapper translating Domain ↔ Persistence models

## Consequences
### Positive
- Domain is framework-agnostic and easy to unit test.
- Clear boundary between business rules and technical details.
- Lower risk of “ORM leakage” into business model.

### Negative
- Requires mapping code (Domain ↔ Eloquent).
- Slightly more work to persist aggregates (especially collections like invoice lines).

## Alternatives Considered
1. Annotated Doctrine-like entities as domain (DDD-lite)
   - Common in Symfony, but couples domain to ORM metadata.
2. Eloquent as domain (Active Record)
   - Very productive but mixes responsibilities and makes pure unit testing harder.
