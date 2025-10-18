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
    | WhatsApp Bridge Configuration
    |--------------------------------------------------------------------------
    |
    | AUTOMATIC LOAD BALANCING: The system automatically distributes users
    | across available bridge instances. No manual per-user configuration!
    |
    | How it works:
    | - Each user gets assigned to the least-loaded bridge
    | - User stays on same bridge for all their devices
    | - System calculates: max_sessions ÷ max_devices_per_user = max_users_per_bridge
    | - Example: 50 sessions ÷ 3 devices = ~16 users per bridge
    |
    */

    'bridge' => [
        // Authentication
        'token' => env('BRIDGE_TOKEN', 'your-secret-token'),
        'webhook_secret' => env('BRIDGE_WEBHOOK_SECRET', 'webhook-secret'),

        // Single Bridge Mode (for development/small deployments)
        'url' => env('BRIDGE_URL', 'http://localhost'),
        'port' => env('BRIDGE_PORT', 3001),
        'max_sessions_per_instance' => env('BRIDGE_MAX_SESSIONS', 50),

        // Multi-Bridge Mode (for production with auto-scaling)
        // Each bridge handles multiple users automatically
        'instances' => [
            [
                'url' => env('BRIDGE_1_URL', 'https://api.whatsapp-sender.online'),
                'port' => env('BRIDGE_1_PORT', 3001),
                'max_sessions' => env('BRIDGE_1_MAX_SESSIONS', 50), // ~16 users (50÷3)
            ],
            [
                'url' => env('BRIDGE_2_URL', 'https://api.whatsapp-sender.online'),
                'port' => env('BRIDGE_2_PORT', 3002),
                'max_sessions' => env('BRIDGE_2_MAX_SESSIONS', 50), // ~16 users
            ],
            [
                'url' => env('BRIDGE_3_URL', 'https://api.whatsapp-sender.online'),
                'port' => env('BRIDGE_3_PORT', 3003),
                'max_sessions' => env('BRIDGE_3_MAX_SESSIONS', 50), // ~16 users
            ],
            // Add more as needed - system auto-assigns users to least loaded
        ],

        // User Limits (configurable per plan/subscription)
        'max_devices_per_user' => env('MAX_DEVICES_PER_USER', 3),
        'session_timeout_minutes' => env('SESSION_TIMEOUT_MINUTES', 1440), // 24 hours

        /*
        |--------------------------------------------------------------------------
        | Scaling Guide
        |--------------------------------------------------------------------------
        |
        | For 100 users with 3 devices each = 300 total devices needed
        |
        | Option 1: Few powerful bridges
        | - 6 bridges × 50 sessions = 300 devices (100 users)
        |
        | Option 2: Many small bridges
        | - 15 bridges × 20 sessions = 300 devices (100 users)
        |
        | System automatically balances users across all available bridges!
        |
        */
    ],
];
