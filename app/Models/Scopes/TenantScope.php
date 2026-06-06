<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply tenant isolation to all queries.
     * super_admin: sees everything (no filter).
     * client/operator/maintenance/viewer: sees only their client_id data.
     *
     * Reads client_id from service container (set by EnsureTenantContext
     * middleware) for efficiency. Falls back to User model method.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (!auth()->check() || auth()->user()->hasRole('super_admin')) {
            return;
        }

        // Prefer container binding (set by EnsureTenantContext middleware)
        $clientId = app()->bound('tenant.client_id')
            ? app('tenant.client_id')
            : auth()->user()->getTenantClientId();

        if ($clientId) {
            $builder->where($model->getTable() . '.client_id', $clientId);
        }
    }
}
