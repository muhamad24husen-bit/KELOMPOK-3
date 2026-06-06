<?php

namespace App\Models\BEMS;

use Illuminate\Database\Eloquent\Model;

class PendingNode extends Model
{
    protected $fillable = [
        'mac_address',
        'chip_type',
        'firmware_ver',
        'status',
    ];

    /**
     * Scope: only pending (not yet approved/rejected).
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
