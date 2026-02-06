<?php

namespace App\Providers;

// REQUIRED: Use the base EventServiceProvider class
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;

// REQUIRED: Import the event and listener classes
use Laravel\Passport\Events\AccessTokenCreated; 
use App\Listeners\AssignPermissionsOnLogin;

class EventServiceProvider extends ServiceProvider 
{
    protected $listen = [ 
        Registered::class => [
            SendEmailVerificationNotification::class,
        ]
    ];

    public function boot(): void
    {
        // Any logic you want to run on boot
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}