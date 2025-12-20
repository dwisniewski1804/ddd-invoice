# ADR-0001 — DDD-inspired Modular Architecture in a Laravel App

- Status: Accepted
- Date: 2025-12-20

## Context
The task is not primarily CRUD. The complexity lies in business rules and workflow:
- invoice lifecycle (draft → sending → sent-to-client)
- status transitions
- conditions required to send an invoice
- reacting to delivery confirmation via webhook/event

Laravel by default encourages Active Record patterns (Eloquent), which can mix persistence concerns with business logic.

## Decision
Use a DDD-inspired, modular monolith structure:
- Domain: pure PHP business model and invariants
- Application: explicit use-cases (handlers)
- Infrastructure: persistence and integrations
- HTTP: controllers and requests

## Consequences
### Positive
- Business rules are centralized, readable, and unit-testable.
- Domain logic is decoupled from framework and ORM details.
- Easier to evolve the workflow without spreading rules across controllers/models.

### Negative
- More files and some mapping boilerplate.
- Requires discipline to keep domain free from framework dependencies.

## Alternatives Considered
1. Pure Laravel Active Record (fat Eloquent models)
   - Faster for CRUD, but weaker for workflow-heavy domain logic.
2. Full “Clean Architecture” with more layers and abstractions
   - Overkill for the task scope.
