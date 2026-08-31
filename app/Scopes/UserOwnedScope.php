<?php

declare(strict_types=1);

namespace App\Scopes;

use App\Support\UserContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Adds `WHERE <table>.user_id = <current user>` to every query on a model that
 * uses App\Models\Concerns\BelongsToAuthUser.
 *
 * The filter applies only while App\Support\UserContext holds a user id, which
 * happens on routes carrying the `scope.user` middleware. Console commands,
 * queue workers and the admin panel therefore see every row — see
 * UserContext::for() for running work as one particular user.
 *
 * The column is qualified with the table name deliberately: a bare `user_id`
 * turns ambiguous the moment the query joins a second owned table, and the
 * error it produces points at the join, not at this line.
 */
final class UserOwnedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $userId = app(UserContext::class)->userId();

        if ($userId === null) {
            return;
        }

        $builder->where($model->getTable().'.user_id', $userId);
    }
}
