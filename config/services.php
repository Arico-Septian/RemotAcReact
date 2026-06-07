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

    // SNMP — baca suhu CPU Raspberry Pi via snmpwalk (command snmp:raspi-temp).
    // temp_oid default = nsExtendOutLine dari extend "cpu-temp" di snmpd.conf Raspi.
    'snmp' => [
        'enabled' => env('SNMP_ENABLED', false),
        'host' => env('SNMP_HOST', '127.0.0.1'),
        'community' => env('SNMP_COMMUNITY', 'public'),
        'temp_oid' => env('SNMP_TEMP_OID', '.1.3.6.1.4.1.8072.1.3.2.3.1.1'),
    ],

];
