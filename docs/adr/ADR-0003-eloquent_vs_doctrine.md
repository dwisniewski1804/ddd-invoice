# ADR-0003 — Use Eloquent as Infrastructure (Hybrid DDD) Instead of Introducing Doctrine ORM

- Status: Accepted
- Date: 2025-12-20

## Context
Doctrine ORM can be integrated into Laravel, but it adds configuration, learning curve, and reduces “Laravel-native” developer experience.
The task scope does not require advanced ORM features.

## Decision
Use Eloquent strictly as an infrastructure/persistence model:
- Eloquent models represent tables and relations only
- domain remains clean
- repositories and mappers isolate domain from Eloquent

Do not introduce Doctrine ORM for this task.

## Consequences
### Positive
- Keeps Laravel ecosystem and conventions.
- Avoids extra dependencies and setup overhead.
- Still achieves clean domain and testability.

### Negative
- Not a full Data Mapper ORM; mapping logic remains custom.
- Some Eloquent conveniences (scopes, magic) are intentionally not used in domain logic.

## Alternatives Considered
1. Add Doctrine ORM to Laravel
   - Cleaner Data Mapper experience, but heavier and less aligned with Laravel tooling.
