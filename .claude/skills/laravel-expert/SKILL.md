---
name: laravel-expert
description: Use when adding or reviewing backend PHP and it is not obvious where the code belongs — controller, FormRequest, Action, Resource, Policy, Rule or Model — or when a controller/model has grown fat. Answers "where should this live" and flags the usual Laravel smells. Триггеры — «куда положить эту логику», «отрефакторь контроллер», «посмотри бэкенд-код», «правильно ли это по-ларавельному», «раздулся контроллер».
---

# Where backend code belongs

One question decides most of it: *what is this line responsible for?*

| Responsibility | Home | Shape |
|---|---|---|
| Route → response wiring | `App\Http\Controllers\Api\V1\*` | thin: validate, call, return a Resource |
| Input shape and rules | `App\Http\Requests\*` | `rules()`, `authorize()`, translated messages |
| A rule that queries the DB | `App\Rules\*` | e.g. `ScopedExists` — never a bare `exists:` on user data |
| Business operation | `App\Actions\*` | **one** public `handle()`, constructor injection |
| Output shape | `App\Http\Resources\*` | `JsonResource` / `ResourceCollection`, never a bare array |
| Who may do it | `App\Policies\*` extending `BasePolicy` | permission names from the `PermissionName` enum |
| Relations, casts, tiny accessors | `App\Models\*` | no business logic, no HTTP, no queue dispatch |
| Expected failure with a user-facing message | `App\Exceptions\BusinessException` | takes a **translation key**, the renderer translates once |

There is no `app/Repositories/` and no service layer. Eloquent is the data layer; an Action that
needs an unusual query writes it, or the model gets a query scope.

## The Action shape

```php
final readonly class ArchiveThingAction
{
    public function __construct(private Notifier $notifier) {}

    public function handle(Thing $thing): Thing
    {
        // ... one operation, start to finish
    }
}
```

- One public method. If a second one appears, it is a second Action.
- No `static` state (the worker outlives the request — see skill `laravel-architecture`).
- Actions assume authorization already passed; the gate lives on the route or in `authorize()`.

## Smells worth a grep

```bash
grep -rn "DB::raw\|whereRaw" app/                        # unparameterised SQL?
grep -rn "response()->json(\[" app/Http/Controllers/     # bare arrays instead of Resources
grep -rn "abort(4[0-9][0-9], *'" app/                    # hardcoded, untranslated messages
grep -rn "->all()" app/Http/Controllers/                 # mass assignment from raw input
grep -rn "foreach" app/ | grep -n "->relation"           # candidates for N+1
```

- **Fat controller** — anything past "validate, call one Action, return a Resource" moves out.
- **Logic in the model** — a model method that sends mail, dispatches a job or talks to an API is an
  Action wearing a disguise.
- **N+1** — collections always get `with()`. Confirm with the query count in a test, not by eye.
- **Untranslated user-visible string** — every message a user can read goes through `__('key')`, in
  every locale the project ships. See skill `i18n-sync`.
- **Duplicated logic across controllers** — the second copy is the signal to extract an Action.

## Before writing new CRUD by hand

The project is database-first: the migration (with a `->comment()` on **every** column) is written
first, then the generator produces model, controller, requests, resource, policy and factory from the
real schema. Hand-writing that set is a bug, not a preference — see skill `feature-workflow`.

## Reviewing

Report as: what is wrong → why it bites → the smallest change that fixes it. Do not propose a
package where the framework already has the feature, and do not restructure code that is merely not
to your taste.
