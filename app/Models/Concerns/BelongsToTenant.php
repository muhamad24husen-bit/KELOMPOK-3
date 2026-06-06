<?php

namespace App\Models\Concerns;

use App\Models\BEMS\Client;
use App\Models\Scopes\TenantScope;

/**
 * Trait BelongsToTenant
 *
 * Menyediakan fitur multi-tenant isolation untuk model:
 * 1. Global Scope  — otomatis filter WHERE client_id = X (kecuali super_admin)
 * 2. Auto-fill     — isi client_id otomatis saat creating via resolveTenantClientId()
 * 3. Relasi client  — belongsTo(Client::class)
 *
 * Usage:
 *   use BelongsToTenant;
 *
 *   protected function resolveTenantClientId(): ?int
 *   {
 *       return $this->room?->client_id;
 *   }
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // ── Global Scope: tenant isolation ──────────────────────
        static::addGlobalScope(new TenantScope());

        // ── Auto-fill client_id on creating ─────────────────────
        static::creating(function ($model) {
            if (empty($model->client_id)) {
                $model->client_id = $model->resolveTenantClientId();
            }
        });
    }

    /**
     * Relasi ke tabel bems_clients (tenant owner).
     */
    public function tenant()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Resolve client_id dari parent relationship.
     * Setiap model WAJIB mengimplementasi ini.
     *
     * Contoh:
     * - Node:  return $this->room?->client_id;
     * - SensorLog: return Node::find($this->node_id)?->client_id;
     */
    abstract protected function resolveTenantClientId(): ?int;
}
