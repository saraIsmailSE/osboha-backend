<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('REVERB_APP_ID'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'cluster' => env('REVERB_APP_CLUSTER', 'mt1'),
                'useTLS' => true,
                'host' => '127.0.0.1',
                'port' => 6001,
                'scheme' => env('REVERB_APP_SCHEME', 'http'),
                'verify' => env('CURL_CA_BUNDLE', true),
            ],
            'curl_options' => [
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CAINFO => env('CURL_CA_BUNDLE', '/home/osbohaco/ssl/certs/cacert.pem'),
            ],
        ],
        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
