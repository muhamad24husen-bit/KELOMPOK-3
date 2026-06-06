<?php

namespace App\Models\BEMS;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'client_id',
        'staff_role',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
