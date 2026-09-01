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

    'zoho' => [
        'client_id' => env('ZOHO_CLIENT_ID'),
        'client_secret' => env('ZOHO_CLIENT_SECRET'),
        'redirect' => env('ZOHO_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/zoho/callback'),
        // accounts.zoho.com, accounts.zoho.eu, accounts.zoho.in, etc.
        'domain' => env('ZOHO_DOMAIN') ?: (env('ZOHO_ACCOUNTS_URL')
            ? str_replace(['https://', 'http://'], '', env('ZOHO_ACCOUNTS_URL'))
            : 'accounts.zoho.com'),
        // First-time email→account linking is only allowed for these domains when Zoho omits email_verified.
        'trusted_email_domains' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('ZOHO_TRUSTED_EMAIL_DOMAINS', 'laprophan.com'))
        ))),
    ],

];
