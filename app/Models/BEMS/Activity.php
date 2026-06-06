<?php

namespace App\Models\BEMS;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'node_id',
        'client_id',
        'user_id',
        'type',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    protected function resolveTenantClientId(): ?int
    {
        // Activity bisa punya node_id nullable
        if ($this->node_id) {
            return Node::find($this->node_id)?->client_id;
        }

        // Fallback: ambil dari tenant context
        return app()->bound('tenant.client_id') ? app('tenant.client_id') : null;
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
