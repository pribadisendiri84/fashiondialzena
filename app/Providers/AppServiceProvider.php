<?php

namespace App\Providers;

use App\Enums\Ability;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach (Ability::cases() as $ability) {
            Gate::define($ability->value, fn (User $user) => $user->resolvedRole()->allows($ability));
        }
    }
}
