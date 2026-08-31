---
name: laravel-security-audit
description: Use when the user asks for a security review of backend code — before a release, after touching auth, permissions, uploads or raw SQL, or when asking whether one user can reach another user's data. Covers IDOR, mass assignment, injection, rate limiting, exposed debug and misconfiguration, with severity ratings. Триггеры — «проверь на уязвимости», «аудит безопасности», «можно ли увидеть чужие данные», «IDOR», «безопасно ли это», security review.
---

# Security review of a Laravel API

Think like an attacker, report like an engineer. Rate every finding
**Critical / High / Medium / Low / Informational** and do not inflate. An unproven suspicion is
"Informational — needs verification", not "Critical".

## Threat model to hold in mind

Unauthenticated stranger · authenticated low-privilege user · a user of a *different* tenant ·
a former member whose access was revoked · an automated scanner.

## 1. Object-level access (IDOR) — start here

This is where a multi-tenant API actually leaks. Two independent mistakes:

**(a) An unscoped query.** Any read/list that trusts an id from the request.

**(b) Unscoped route-model binding.** `SubstituteBindings` runs **before** the middleware that
establishes the current user, so the global owner scope is not active during binding. A model that
relies only on a global scope is still bindable by id from any account.

The canonical fix in this codebase is **not** a manual `where('user_id', …)` in each controller —
that fights the global scope, has to be repeated at every call site, and silently collapses any
shared/tenant visibility the scope grants. The fix is structural:

```php
// app/Models/Concerns/BelongsToAuthUser.php — one override, EVERY model using the trait
public function resolveRouteBinding($value, $field = null): ?Model
{
    $user = auth()->user();

    if ($user === null) {
        return null;                     // resolve nothing → the router raises a 404
    }

    $table = $this->getTable();

    return static::query()
        ->where($table.'.'.($field ?: $this->getRouteKeyName()), $value)
        ->where($table.'.user_id', $user->getAuthIdentifier())
        ->first();
}
```

Two details that are easy to get wrong when reviewing a variant of this:

- The unauthenticated branch must resolve **nothing**. A "helpful" fallback to an unscoped lookup
  turns any public route that binds an owned model into a full-table leak.
- The column is qualified with the table name. Unqualified `user_id` becomes ambiguous the moment
  the query joins a second owned table, and the failure mode is a wrong row, not an error.

With that in place, controller queries need no manual owner filter — `Thing::findOrFail()` inside the
controller already goes through the global scope, because the user context is set by then. Verify
that a new model actually uses the trait:

```bash
grep -rLn "BelongsToAuthUser" app/Models/*.php     # models WITHOUT the trait — is each one really public?
```

Audit steps:

```bash
grep -rn "findOrFail(\$request->\|find(\$request->\|::find(" app/Http/Controllers/
grep -rn "withoutGlobalScope" app/                 # every bypass needs a justification
grep -rn "'exists:" app/Http/Requests/             # exists: ignores ownership — use ScopedExists
```

`exists:things,id` validates that the row exists **anywhere**, for anyone. On user-owned tables it is
an id oracle; use `App\Rules\ScopedExists`.

Derived data — statistics, aggregates, reports, raw SQL — is **not** covered by any global scope.
Each such query must filter explicitly. List them and check them one by one.

## 2. The rest of the sweep

| Area | What to check |
|---|---|
| Mass assignment | `$fillable` on every model; no `Model::create($request->all())` |
| Authorization | route middleware or `FormRequest::authorize()` on every non-public route; admin routes behind a permission, not a role string on the user row |
| Injection | `DB::raw`/`whereRaw` with interpolated input; `orderBy` taken from the query string |
| Authentication | password hashing untouched; token expiry set; logout actually deletes the token; no secrets or hashes in any Resource |
| Uploads | extension **and** MIME validated, size capped, stored outside the public root, filename not taken from the client |
| Rate limiting | throttle on login, registration, password reset, and any endpoint a client can loop |
| Output | no `{!! !!}` on user content; Resources whitelist fields instead of dumping the model |
| Config | `APP_DEBUG=false` in production; trusted proxies configured; CORS origins not `*` when credentials are involved; `.env` unreachable over HTTP |
| Logs | no tokens, passwords or full request bodies written to logs or the error channel |

## Report format

For each finding: **Title · Severity · Where (file:line) · Exploit in one sentence · Fix**, followed by
the smallest patch that closes it. Then a one-line summary of what was checked and found clean —
that is what makes the report trustworthy.

Do not invent vulnerabilities, do not assume a production topology that was not described, and do not
recommend a security package where the framework already covers it.
