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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Notifications aux parents : toujours dans le portail, et par e-mail si activé
    // (nécessite une configuration MAIL_* valide dans .env)
    'parents' => [
        'mail' => (bool) env('PARENTS_NOTIFICATIONS_MAIL', false),
    ],

    // Annonces importantes et urgentes : notification dans l'application, et par e-mail si activé
    'annonces' => [
        'mail' => (bool) env('ANNONCES_MAIL', false),
    ],

];
