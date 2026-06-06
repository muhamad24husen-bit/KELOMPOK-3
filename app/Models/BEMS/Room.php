<?php

namespace App\Models\BEMS;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Room extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'client_id',
        'name',
        'slug',
        'floor',
        'category',
        'total_nodes',
        'status',
        'icon',
    ];

    protected static function booted(): void
    {
        // Auto-generate slug saat create
        static::creating(function ($room) {
            if (empty($room->slug)) {
                $room->slug = Str::slug($room->name);
            }
        });

        // Update slug jika nama berubah
        static::updating(function ($room) {
            if ($room->isDirty('name')) {
                $room->slug = Str::slug($room->name);
            }
        });
    }

    protected function resolveTenantClientId(): ?int
    {
        // Room sudah punya client_id langsung
        return $this->client_id;
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    public function nodes()
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Count nodes that are currently online.
     */
    public function activeNodesCount(): int
    {
        return $this->nodes()->where('status', 'online')->count();
    }
}
