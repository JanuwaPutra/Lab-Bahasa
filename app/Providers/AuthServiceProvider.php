<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->defineGates();
    }

    protected function defineGates(): void
    {
        // Define gate for admin role
        Gate::define('admin', function (User $user) {
            return $user->role === 'admin';
        });
        
        // Define gate for teacher role
        Gate::define('teacher', function (User $user) {
            return $user->role === 'teacher';
        });
        
        // Define gate for evaluation settings
        Gate::define('viewEvaluationSettings', function (User $user) {
            return $user->role === 'teacher' || $user->role === 'admin';
        });
        
        // Define gate for updating evaluation settings
        Gate::define('updateEvaluationSettings', function (User $user) {
            return $user->role === 'teacher' || $user->role === 'admin';
        });
    }
} 