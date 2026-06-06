<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\BEMS\Client;
use App\Models\BEMS\Staff;
use App\Models\ST\Program;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'code',
        'user_id',
        'expirity',
        'kelas',
        'gedung',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function program()
    {
        return $this->hasOne(Program::class, 'user_id', 'id');
    }

    // ── BEMS Relationships ──────────────────────────────────

    /**
     * Relasi ke bems_clients (untuk role: client — pemilik gedung).
     */
    public function bemsClient()
    {
        return $this->hasOne(Client::class, 'user_id');
    }

    /**
     * Relasi ke staff (untuk role: maintenance, operator, viewer).
     */
    public function staff()
    {
        return $this->hasOne(Staff::class, 'user_id');
    }

    /**
     * Ambil client_id yang relevan untuk user ini.
     * Digunakan oleh TenantScope untuk isolasi data multi-tenant.
     */
    public function getTenantClientId(): ?int
    {
        if ($this->hasRole('super_admin')) {
            return null; // super_admin melihat semua data
        }

        if ($this->hasRole('client')) {
            return $this->bemsClient?->id;
        }

        if ($this->hasAnyRole(['maintenance', 'operator', 'viewer'])) {
            return $this->staff?->client_id;
        }

        return null;
    }

    // ── Role Helpers ────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    public function isOperator(): bool
    {
        return $this->hasRole('operator');
    }

    public function isMaintenance(): bool
    {
        return $this->hasRole('maintenance');
    }

    public function isViewer(): bool
    {
        return $this->hasRole('viewer');
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['maintenance', 'operator', 'viewer']);
    }
}
