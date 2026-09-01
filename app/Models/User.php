<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, RecordsActivity, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return Attribute<UserRole, UserRole|string|null>
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => UserRole::fromDatabase($value),
            set: fn (UserRole|string|null $value) => $value instanceof UserRole ? $value->value : $value,
        );
    }

    public function resolvedRole(): UserRole
    {
        return $this->role ?? UserRole::Admin;
    }

    public function isSuperadmin(): bool
    {
        return $this->resolvedRole() === UserRole::Superadmin;
    }

    public function isAdmin(): bool
    {
        return $this->resolvedRole() === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->resolvedRole() === UserRole::Staff;
    }

    public function isSales(): bool
    {
        return $this->resolvedRole() === UserRole::Sales;
    }

    public function adminHomeRouteName(): string
    {
        return match ($this->resolvedRole()) {
            UserRole::Superadmin, UserRole::Admin => 'admin.dashboard',
            UserRole::Staff => 'admin.products.index',
            UserRole::Sales => 'admin.sales.index',
        };
    }
}
