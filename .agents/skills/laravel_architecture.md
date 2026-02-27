---
name: Laravel Advanced Architecture
description: Outlines best practices for using Laravel Octane, Horizon, and Actions.
---
# Laravel Advanced Architecture Skill

When instructed to refactor or build complex logic, adhere to these architectural standards:

### 1. Octane (FrankenPHP) Compliance
Because we use Laravel Octane:
- DO NOT use the `app()` helper to resolve singletons if their state changes per request.
- **STRICT PROHIBITION**: Do not use `static` properties or `static` variables inside Services, Actions, or Repositories. Since the worker process persists, static variables will leak data across different user requests.
- Avoid modifying global state or utilizing singletons that store user-specific state.
- Be extremely careful with memory leaks (e.g., appending data to a static array over time).

### 2. Job & Queue Management (Horizon)
- All long-running tasks MUST be dispatched as Jobs.
- Make all Jobs idempotent. If a job runs twice due to a failure and retry, it should not corrupt the database.
- Use `ShouldBeUnique` for jobs that process states (e.g., syncing a delivery order if one is already queued).

### 3. Permissions (Spatie)
- Check permissions on the Route level (using `can` middleware) OR inside the FormRequest `authorize()` method.
- Avoid scattering `$user->hasPermissionTo()` throughout the business logic Action classes. Actions should assume authorization has already passed.

### 4. Custom CRUD Generator (`votapil/votacrudgenerator`)
- We use a database-first CRUD generator. Identify its custom stubs and follow the generated patterns.
- NEVER scaffold Models, Controllers, or Requests manually if a new table is created. Always instruct the user to run migrations first (with `->comment()` on columns), then run `make artisan args="vota:crud {ModelName}"`. The AI context will automatically pick up the schema comments.
