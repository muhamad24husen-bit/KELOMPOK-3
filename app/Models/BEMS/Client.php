<?php

namespace App\Models\BEMS;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    protected $table = 'bems_clients';
    protected $guarded = [];

    protected $fillable = [
        'code',
        'slug',
        'user_id',
        'name',
        'expirity',
        'kelas',
        'gedung',
        'total_rooms',
        'thumbnail',
    ];

    protected static function booted(): void
    {
        // Auto-generate slug saat create jika kosong
        static::creating(function ($client) {
            if (empty($client->slug)) {
                $client->slug = Str::slug($client->code ?? $client->name);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function staffMembers()
    {
        return $this->hasMany(Staff::class);
    }

    public function operators()
    {
        return $this->hasMany(Staff::class)->where('staff_role', 'operator');
    }

    public function maintenanceStaff()
    {
        return $this->hasMany(Staff::class)->where('staff_role', 'maintenance');
    }

    public function viewers()
    {
        return $this->hasMany(Staff::class)->where('staff_role', 'viewer');
    }
}
