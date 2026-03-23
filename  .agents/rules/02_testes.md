# Testing Guidelines (Mocking Required)

## Rule
All tests MUST use mocks/fakes for external dependencies.

Tests must NEVER depend on real infrastructure such as:
- Database
- External APIs
- File system
- Email services

---

## Principles

### 1. External Dependencies
Mock all external systems:
- Databases (use repository fakes/mocks)
- APIs (simulate responses)
- Email/queue/storage services

Goal:
Ensure tests validate only business logic, not infrastructure.

---

### 2. Deterministic Behavior
Tests must be fully predictable.

Always simulate:
- API errors (e.g., 500, timeout)
- Database failures
- Empty or edge-case responses

Tests must NOT rely on:
- Real system state
- Existing database data

---

### 3. Speed and Isolation
Tests must be:
- Fast (run in memory)
- Isolated (no shared state)

Avoid:
- Network calls
- Disk access
- Real database connections

---

### 4. Non-Deterministic Behavior
Mock or fix values for:
- Current date/time
- Random numbers
- UUID generation (if needed)

Example:
- Freeze time
- Inject random generators

---

## Implementation Strategy

- Use dependency injection
- Depend on interfaces (Repository Pattern)
- Replace implementations with mocks in tests

---

## What to Test

Focus on:
- UseCases (Application layer)
- Domain logic

Avoid:
- Testing framework behavior
- Testing database internals

---

## Anti-Patterns (Forbidden)

- Using real database in unit tests
- Calling external APIs in tests
- Relying on current system time
- Shared state between tests

---

## Definition of Done (Tests)

- No external calls executed
- Fully deterministic
- Fast execution
- Covers business rules