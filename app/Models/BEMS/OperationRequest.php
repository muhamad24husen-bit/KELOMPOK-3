<?php

namespace App\Models\BEMS;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OperationRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'node_id',
        'client_id',
        'requested_by',
        'approved_by',
        'action',
        'payload',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'responded_at' => 'datetime',
    ];

    protected function resolveTenantClientId(): ?int
    {
        return Node::find($this->node_id)?->client_id;
    }

    // ── Relationships ──────────────────────────────────────────

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Status Checks ──────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isExecuted(): bool
    {
        return $this->status === 'executed';
    }

    // ── Actions ────────────────────────────────────────────────

    /**
     * Approve request oleh operator.
     */
    public function approve(User $operator): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        return $this->update([
            'status'       => 'approved',
            'approved_by'  => $operator->id,
            'responded_at' => now(),
        ]);
    }

    /**
     * Reject request oleh operator.
     */
    public function reject(User $operator): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        return $this->update([
            'status'       => 'rejected',
            'approved_by'  => $operator->id,
            'responded_at' => now(),
        ]);
    }

    /**
     * Tandai request sebagai sudah dieksekusi (setelah MQTT command terkirim).
     */
    public function markExecuted(): bool
    {
        if (! $this->isApproved()) {
            return false;
        }

        return $this->update([
            'status' => 'executed',
        ]);
    }
}
