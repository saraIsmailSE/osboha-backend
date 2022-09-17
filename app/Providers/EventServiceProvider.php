<?php

namespace App\Providers;

use App\Models\Book;
use App\Models\User;
use App\Models\Mark;
use App\Observers\BookObserver;
use App\Observers\UserObserver;
use App\Observers\MarkObserver;
use Illuminate\Auth\Events\Registered;
use App\Events\UserStats;
use App\Events\MarkStats;
use App\Listeners\AddUserToStatistic;
use App\Listeners\AddMarkToStatistic;


use Illuminate\Auth\Listeners\SendEmailVerificationNotification;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        MarkStats::class => [
            AddMarkToStatistic::class,
        ]
    ];
    protected $subscribe = [
       AddUserToStatistic::class
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        // Book::observe(BookObserver::class);
        // User::observe(UserObserver::class);
        // Mark::observe(MarkObserver::class);
    }
}
