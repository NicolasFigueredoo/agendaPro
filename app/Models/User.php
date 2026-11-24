<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'active' => 'boolean',
        ];
    }

    /** Profesional vinculado (para que vea su agenda). */
    public function professional(): HasOne
    {
        return $this->hasOne(Professional::class);
    }

    public function hasRole(Role|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $role = $role instanceof Role ? $role : Role::from($role);

            if ($this->role === $role) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isRecepcion(): bool
    {
        return $this->role === Role::Recepcion;
    }

    public function isProfesional(): bool
    {
        return $this->role === Role::Profesional;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
