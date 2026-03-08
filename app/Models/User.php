<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // Role sederhana: staff|ho
        'role',
        // Backward compat (jika ada kolom lama)
        'is_admin',
    ];

    /**
     * Hidden attributes.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Role helper: HO (super user operasional).
     * - Jika pakai kolom role: role === 'ho'
     * - Jika masih ada kolom is_admin lama: is_admin == true dianggap HO (compat)
     */
    public function isHO(): bool
    {
        $role = strtolower((string) ($this->role ?? ''));
        return $role === 'ho' || (bool) ($this->is_admin ?? false);
    }

    public function isStaff(): bool
    {
        return !$this->isHO();
    }

    /**
     * Backward compatibility untuk kode lama (BaseController / dll).
     * Contoh: hasRole('ho') / hasRole('staff')
     */
    public function hasRole(string $role): bool
    {
        $role = strtolower(trim($role));
        if ($role === 'ho' || $role === 'admin') {
            return $this->isHO();
        }
        if ($role === 'staff' || $role === 'user') {
            return $this->isStaff();
        }
        // unknown role => false
        return false;
    }
}
