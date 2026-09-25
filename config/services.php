<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    'bytespeed' => [
        'url' => env('BYTESPEED_URL'),
        'token' => env('BYTESPEED_TOKEN'),
    ],

    'ai_sency' => [

        'url' => env('AISENCY_URL'),

        'token' => env('AISENCY_TOKEN'),

        'campaigns' => [

            'shipped' =>
            env('AISENCY_CAMPAIGN_SHIPPED'),

            'in_transit' =>
            env('AISENCY_CAMPAIGN_IN_TRANSIT'),

            'out_for_delivery' =>
            env('AISENCY_CAMPAIGN_OFD'),

            'on_hold' =>
            env('AISENCY_CAMPAIGN_HOLD'),

            'delivered' =>
            env('AISENCY_CAMPAIGN_DELIVERED'),

        ],

    ],

    'delhivery' => [
        'base_url' => env('DELHIVERY_BASE_URL'),
        'api_token' => env('DELHIVERY_API_TOKEN'),
        'client_name' => env('DELHIVERY_CLIENT_NAME'),
        'pickup_location' => env('DELHIVERY_PICKUP_LOCATION'),
        'origin_pincode' => env('DELHIVERY_ORIGIN_PINCODE'),
    ],
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
