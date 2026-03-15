<?php

namespace App\Providers;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [],
        Logout::class => [],
    ];

    public function boot(): void
    {
        parent::boot();

        Event::listen(Login::class, function (Login $event) {
            app(ActivityLogService::class)->log(
                action: 'user.login',
                description: 'User logged in',
                subject: $event->user,
                properties: [
                    'email' => $event->user->email,
                    'role' => $event->user->role,
                ],
                request: request(),
                userId: $event->user->id
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                app(ActivityLogService::class)->log(
                    action: 'user.logout',
                    description: 'User logged out',
                    subject: $event->user,
                    properties: [
                        'email' => $event->user->email,
                        'role' => $event->user->role,
                    ],
                    request: request(),
                    userId: $event->user->id
                );
            }
        });
    }
}
