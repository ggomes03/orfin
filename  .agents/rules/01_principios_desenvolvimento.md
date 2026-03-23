# Backend Architecture Spec (TDD + DDD + SOLID)
## Overview
This architecture follows:
- DDD (Domain-Driven Design)
- TDD (Test-Driven Development)
- SOLID principles
Goal:
- Isolate business rules (domain)
- Decouple infrastructure
- Ensure testability and maintainability
---
## Layers
### 1. Domain (Core)
- Contains pure business logic
- No external dependencies
**Components:**
- Entities
- Value Objects
- Repository Interfaces
- Domain Services (if needed)
**Rules:**
- No framework imports
- No database access
- Must be fully testable in isolation
---
### 2. Application (Use Cases)
- Orchestrates domain logic
- Defines system behavior
**Components:**
- Use Cases
- DTOs (input/output)
**Rules:**
- Depends only on Domain
- Uses repository interfaces (not implementations)
- No HTTP / DB / framework logic
---
### 3. Infrastructure
- Implements external concerns
**Components:**
- Database (ORM, queries)
- Repository Implementations
- External APIs
**Rules:**
- Implements Domain interfaces
- Can depend on frameworks/libs
- No business rules
---
### 4. Interface (Delivery Layer)
- Entry points (HTTP, CLI, etc.)
**Components:**
- Controllers / Routes
**Rules:**
- Receives input
- Calls Use Cases
- Returns output
- No business logic
---
## Folder Structure
app/
├── Domain/
│ ├── Entities/
│ ├── ValueObjects/
│ ├── Repositories/ # interfaces
│ └── Services/
│
├── Application/
│ ├── UseCases/
│ └── DTOs/
│
├── Infrastructure/
│ ├── Persistence/
│ │ ├── Eloquent/
│ │ └── Repositories/ # implementations
│ └── Services/ # external integrations
│
├── Interfaces/
│ ├── Http/
│ │ ├── Controllers/
│ │ └── Requests/
│
└── Providers/ # bindings (DI)
tests/
├── Unit/
└── Feature/
---
## Dependency Rule
Allowed:
- interface → application → domain
- infrastructure → domain
Not allowed:
- domain → any other layer
- application → infrastructure
---
## SOLID Principles
- S: One responsibility per class
- O: Extend without modifying
- L: Respect interface contracts
- I: Small, specific interfaces
- D: Depend on abstractions
---
## TDD Workflow
1. Write failing test
2. Implement minimal code to pass
3. Refactor
---
## Testing Strategy
- Domain: pure unit tests
- Application: use case tests (with fakes/mocks)
- Infrastructure: integration tests
- Interface: optional (e2e)
---
## Patterns
- Dependency Injection
- Repository Pattern
- Factory (optional for wiring)
- Value Objects for validation
---
## Key Rules
- Business logic ONLY in Domain
- Use Cases orchestrate, do not implement rules
- Infra is replaceable
- Tests must not depend on real database
---
## Example Flow
Request → Controller → UseCase → Domain → Repository Interface → Infra Implementation
---
## Anti-Patterns (Avoid)

- Fat controllers
- Business logic in services/controllers
- Direct ORM usage in Use Cases
- Tight coupling to frameworks
- Skipping tests
---
## Definition of Done
- Use case covered by tests
- No infra dependency in domain/application
- Code follows SOLID
- Clear separation of concerns