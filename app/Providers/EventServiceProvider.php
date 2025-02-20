<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\ConversationStatusChanged;
use App\Events\ConversationsChange;
use Illuminate\Support\Facades\Log;


class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ConversationStatusChanged::class => [],
        ConversationsChange::class => [], 
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
        
        // Add debug logging for event broadcasting
        Event::listen('*', function ($eventName, array $data) {
            if (str_contains($eventName, 'ConversationsChange')) {
                Log::info("Broadcasting event: {$eventName}", [
                    'data' => $data,
                    'time' => now()->toIso8601String()
                ]);
            }
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
