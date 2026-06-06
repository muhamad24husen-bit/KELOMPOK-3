<?php

namespace App\Models\BEMS;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class SensorLog extends Model
{
    use MassPrunable;
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'node_id',
        'client_id',
        'metric',
        'value',
        'recorded_at',
    ];

    protected function resolveTenantClientId(): ?int
    {
        // Node sudah punya client_id (denormalisasi)
        return Node::find($this->node_id)?->client_id;
    }

    protected $casts = [
        'recorded_at' => 'datetime',
        'value'       => 'decimal:2',
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Scope: get logs from the last 24 hours.
     */
    public function scopeLast24Hours(Builder $query): Builder
    {
        return $query->where('recorded_at', '>=', now()->subHours(24));
    }

    /**
     * Prune logs older than 90 days to prevent table bloat.
     */
    public function prunable(): Builder
    {
        return static::where('recorded_at', '<', now()->subDays(90));
    }
}
