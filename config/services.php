<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Bridge Configuration - Multi-Port Architecture
    |--------------------------------------------------------------------------
    |
    | MULTI-PORT ISOLATION: Each port runs a separate WhatsApp instance
    | - One port = One user (complete isolation)
    | - Ports 3001-8000 = 5000 simultaneous users
    | - Each instance has its own database file
    | - Automatic port assignment via BridgeManager
    |
    */

    'bridge' => [
        // Authentication
        'token' => env('BRIDGE_TOKEN', 'e1657fd03836f797700bdd1fd1f3b47786c3098344ab5b92f16ce9e19092c55f'),
        'webhook_secret' => env('BRIDGE_WEBHOOK_SECRET', 'webhook-secret'),

        // Multi-Port Configuration (5000 ports = 5000 users)
        'instances' => [
            [
                'url' => env('BRIDGE_1_URL', 'https://api.whatsapp-sender.online'),
                'port_range_start' => 3001,
                'port_range_end' => 8000,  // 5000 ports
                'max_sessions' => 5000,
            ],
            // Add more servers here as you scale:
            // [
            //     'url' => env('BRIDGE_2_URL', 'https://api2.whatsapp-sender.online'),
            //     'port_range_start' => 3001,
            //     'port_range_end' => 8000,
            //     'max_sessions' => 5000,
            // ],
        ],

        // User Limits (configurable per plan/subscription)
        'max_devices_per_user' => env('MAX_DEVICES_PER_USER', 1),
        'session_timeout_minutes' => env('SESSION_TIMEOUT_MINUTES', 1440), // 24 hours

        // Legacy single-port config (not used in multi-port mode)
        'url' => env('BRIDGE_URL', 'https://api.whatsapp-sender.online'),
        'port' => env('BRIDGE_PORT', 3001),
        'max_sessions_per_instance' => env('BRIDGE_MAX_SESSIONS', 5000),
    ],
];
