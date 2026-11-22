<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The users provider for every guard: a closed account is never found, so its sessions, a
 * remember-me cookie, a password reset and a sign-in all treat it as unknown, while the account's
 * row and its records stay in place (a soft delete).
 */
final class OpenAccountUserProvider extends EloquentUserProvider
{
    /**
     * @param  Model|null  $model
     * @return Builder<Model>
     */
    protected function newModelQuery($model = null): Builder
    {
        return parent::newModelQuery($model)->whereNull('closed_at');
    }
}
