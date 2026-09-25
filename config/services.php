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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-flash-lite-latest'),
    ],

    // Carte bancaire uniquement désormais — Wave et Orange Money ont leur propre intégration
    // directe ci-dessous (target_payment n'est donc plus utilisé par PayTechService).
    'paytech' => [
        'key' => env('PAYTECH_API_KEY'),
        'secret' => env('PAYTECH_API_SECRET'),
        'env' => env('PAYTECH_ENV', 'test'),
        // Optionnelles : surchargent les URL générées dynamiquement (route()) par des URL fixes
        // — utile en local, où APP_URL est en http:// et où PayTech exige du https:// pour
        // ipn_url (ex. un tunnel ngrok/cloudflared pointé sur cette instance).
        'success_url' => env('PAYTECH_SUCCESS_URL'),
        'cancel_url' => env('PAYTECH_CANCEL_URL'),
        'ipn_url' => env('PAYTECH_IPN_URL'),
        // Compensation temporaire : le tableau de bord marchand PayTech ("TECHRISE") ajoute des
        // frais à la charge du CLIENT sur la page de paiement (ex. 5 600 FCFA commandés → 5 712
        // FCFA affichés, +2%) — aucun paramètre d'API documenté ne permet de configurer qui paie
        // ces frais, ça se règle normalement depuis le tableau de bord PayTech lui-même. En
        // attendant, ce taux réduit le montant envoyé à PayTech pour que le montant final
        // affiché au client retombe sur le total réel de la commande. 0 = désactivé.
        'fee_rate' => (float) env('PAYTECH_FEE_RATE', 0),
    ],

    // Intégration directe Wave Business (docs.wave.com) — Checkout Sessions.
    'wave' => [
        'key' => env('WAVE_API_KEY'),
        'webhook_secret' => env('WAVE_WEBHOOK_SECRET'),
        'success_url' => env('WAVE_SUCCESS_URL'),
        'error_url' => env('WAVE_ERROR_URL'),
    ],

    // Intégration directe Orange Money Web Payment (developer.orange.com/apis/om-webpay).
    // `country` est le code pays fourni par Orange dans l'espace développeur au moment de
    // l'activation du compte marchand (ex. "sn" pour le Sénégal) — jamais deviné ici.
    'orange_money' => [
        'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
        'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
        'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY'),
        'country' => env('ORANGE_MONEY_COUNTRY', 'sn'),
        'success_url' => env('ORANGE_MONEY_SUCCESS_URL'),
        'cancel_url' => env('ORANGE_MONEY_CANCEL_URL'),
        'notif_url' => env('ORANGE_MONEY_NOTIF_URL'),
    ],

    // Intégration directe PayDunya (developers.paydunya.com) — Djamo (SN) et Free Money,
    // moyens non couverts par PayTech/Wave/Orange Money. PayDunya délivre deux jeux de 4 clés
    // distincts (tableau de bord → API Keys) — test et live — jamais interchangeables. Les deux
    // jeux restent en .env en même temps ; seul PAYDUNYA_MODE choisit lequel est utilisé, pour
    // pouvoir repasser en test à tout moment sans ressaisir les clés.
    'paydunya' => [
        'mode' => env('PAYDUNYA_MODE', 'test'),
        'master_key' => env('PAYDUNYA_MODE') === 'live' ? env('PAYDUNYA_LIVE_MASTER_KEY') : env('PAYDUNYA_TEST_MASTER_KEY'),
        'private_key' => env('PAYDUNYA_MODE') === 'live' ? env('PAYDUNYA_LIVE_PRIVATE_KEY') : env('PAYDUNYA_TEST_PRIVATE_KEY'),
        'public_key' => env('PAYDUNYA_MODE') === 'live' ? env('PAYDUNYA_LIVE_PUBLIC_KEY') : env('PAYDUNYA_TEST_PUBLIC_KEY'),
        'token' => env('PAYDUNYA_MODE') === 'live' ? env('PAYDUNYA_LIVE_TOKEN') : env('PAYDUNYA_TEST_TOKEN'),
        'store_name' => env('PAYDUNYA_STORE_NAME', env('APP_NAME', 'Khalil Déco')),
        'store_website_url' => env('PAYDUNYA_STORE_WEBSITE_URL', env('APP_URL')),
        'success_url' => env('PAYDUNYA_SUCCESS_URL'),
        'cancel_url' => env('PAYDUNYA_CANCEL_URL'),
        'callback_url' => env('PAYDUNYA_CALLBACK_URL'),
    ],

];
