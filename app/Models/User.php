<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function resolvedRole(): UserRole
    {
        return $this->role ?? UserRole::Owner;
    }

    public function isOwner(): bool
    {
        return $this->resolvedRole() === UserRole::Owner;
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
            UserRole::Owner => 'admin.dashboard',
            UserRole::Staff => 'admin.products.index',
            UserRole::Sales => 'admin.sales.index',
        };
    }
}
