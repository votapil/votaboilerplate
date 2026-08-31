<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Validate that a referenced id exists AND is visible to the current user.
 *
 * Use this instead of the string rule `exists:table,id`. That rule compiles to
 * a raw SQL EXISTS against the table, which means it walks straight past the
 * global scope and past soft deletes: a caller could post someone else's id,
 * sail through validation, and only be stopped later — or not at all, if the
 * controller trusts validated input. Resolving through the model keeps the
 * owner filter in play.
 *
 *   'project_id' => ['required', new ScopedExists(Project::class)],
 *
 * App\Support\UserContext is populated before validation runs, so this works in
 * FormRequests and in inline controller validation alike.
 *
 * @template TModel of Model
 */
final class ScopedExists implements ValidationRule
{
    /** @param class-string<TModel> $model */
    public function __construct(private readonly string $model) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! $this->model::query()->whereKey($value)->exists()) {
            $fail(__('validation.exists', ['attribute' => $attribute]));
        }
    }
}
