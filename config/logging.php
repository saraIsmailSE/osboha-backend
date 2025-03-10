<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
        'auditMarks' => [
            'driver' => 'single',
            'path' => storage_path('logs/auditMarks.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'newWeek' => [
            'driver' => 'single',
            'path' => storage_path('logs/newWeek.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'RolesAdministration' => [
            'driver' => 'single',
            'path' => storage_path('logs/RolesAdministration.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'books' => [
            'driver' => 'single',
            'path' => storage_path('logs/books.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'newUser' => [
            'driver' => 'single',
            'path' => storage_path('logs/newUser.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'media' => [
            'driver' => 'single',
            'path' => storage_path('logs/media.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'community_edits' => [
            'driver' => 'single',
            'path' => storage_path('logs/community_edits-' . now()->format('Y-W') . '.log'),
            'level' => 'debug',
        ],
        'user_search' => [
            'driver' => 'single',
            'path' => storage_path('logs/user_search-' . now()->format('Y-W') . '.log'),
            'level' => 'debug',
        ],
        'ramadanDay' => [
            'driver' => 'single',
            'path' => storage_path('logs/ramadanDay.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'Questions' => [
            'driver' => 'single',
            'path' => storage_path('logs/Questions.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'Notification' => [
            'driver' => 'single',
            'path' => storage_path('logs/Notification.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'NotificationBroadcasting' => [
            'driver' => 'single',
            'path' => storage_path('logs/NotificationBroadcasting.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'MessagingBroadcasting' => [
            'driver' => 'single',
            'path' => storage_path('logs/MessagingBroadcasting.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'Comments' => [
            'driver' => 'single',
            'path' => storage_path('logs/MessagingBroadcasting.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
    ],

];
