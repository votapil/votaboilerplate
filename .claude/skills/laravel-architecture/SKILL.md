---
name: laravel-architecture
description: Use when the work touches queues, jobs, workers, schedulers or long-lived worker state, or when a bug looks like "fine on the first request, wrong on the next" / "user A saw user B's data". Covers job idempotency, retry storms, transaction vs event ordering, state leaks in a persistent PHP worker. Триггеры — «вынеси в очередь», «джоба», «воркер», «фоновая задача», «падает через раз», «утечка состояния», «данные другого пользователя».
---

# Backend architecture under a persistent worker

The app runs on FrankenPHP + Laravel Octane: **the PHP process survives between requests**, and
queue work runs in a separate worker container. Most of the traps below do not exist on classic
php-fpm, which is why they get written by default and found in production.

## 1. Hunt state that survives the request

Run these before shipping anything that holds data in a class:

```bash
grep -rn "static \$" app/ --include=*.php          # static properties in services/actions
grep -rn "static function .*static \$" app/        # static locals inside methods
grep -rn "singleton(" app/Providers/               # singletons that may hold per-user state
```

Rules that follow from the runtime, not from taste:

- No `static` properties or `static` locals in Actions, Services, Controllers, Jobs. The worker keeps
  them, so request 2 reads request 1's data. This is the single most expensive class of bug here.
- A container **singleton is fine only if it is reset per request**. The request-scoped user context
  (`App\Support\UserContext`) is a singleton exactly for this reason — it has an explicit `forget()`
  and the scope middleware sets it on every request. Anything else holding user data must do the same.
- Package caches that key on the current user (permissions, for example) need their Octane reset
  listener enabled, otherwise a role granted in the admin panel stays invisible until the container
  restarts.

## 2. Make every job safe to run twice

The queue guarantees *at least* once, never exactly once.

- Write the handler so a second run is a no-op: check the target state first, or key the write on a
  natural unique column. "Insert a row" is not idempotent; "insert if absent" is.
- `ShouldBeUnique` for jobs that drive a state machine (one sync per entity at a time).
- Set `$tries` and `$backoff` deliberately. A high `tries` on a job that fails for an external reason
  turns one broken dependency into a retry storm that eats DB connections, sockets and DNS for
  everything else on the box. When in doubt: few tries, long backoff, and a `failed()` that reports.
- Long or rate-limited external calls get their own queue name, so they cannot starve the lane that
  users are waiting on.

```bash
grep -rn "public \$tries\|public \$backoff\|ShouldBeUnique" app/Jobs/
```

## 3. Order: work, then transaction, then event

```php
$payload = $this->client->fetch($id);          // slow / fallible work OUTSIDE the transaction

$model = DB::transaction(function () use ($payload) {
    return Thing::create($payload);            // short, purely local
});

ThingCreated::dispatch($model->id);            // AFTER commit, and ID only — never the model
```

- Never call an HTTP API, a filesystem or a queue from inside `DB::transaction()`. The lock is held
  for the whole latency, and a rollback cannot un-send the side effect.
- Never dispatch domain events from inside the transaction closure or from an Eloquent observer that
  runs in it — the listener may fire on data that gets rolled back.
- Pass IDs in event payloads. A serialized model in a queued listener is a stale snapshot.

## 4. Where authorization happens

Check permissions at the edge — route middleware (`permission:`, `role:`) or `FormRequest::authorize()`
— and let Actions assume the caller is allowed. Permission checks scattered through business logic are
untestable and get skipped by the next caller.

## 5. Guard external calls

Anything that leaves the process (payment gateway, mail API, parser service) goes through
`App\Support\CircuitBreaker`. A dependency that is down should fail fast for a while, not queue up
thousands of retries.

## Verify

```bash
make test                     # parallel suite
make artisan args="queue:failed"
make artisan args="horizon:status"
```
