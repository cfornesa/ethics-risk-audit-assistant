<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Risk Threshold Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the risk score thresholds for different risk levels and
    | determine when automated actions should be triggered.
    |
    */

    'risk_thresholds' => [
        'low' => [
            'min' => 0,
            'max' => 25,
        ],
        'medium' => [
            'min' => 26,
            'max' => 50,
        ],
        'high' => [
            'min' => 51,
            'max' => 75,
        ],
        'critical' => [
            'min' => 76,
            'max' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automated Actions
    |--------------------------------------------------------------------------
    |
    | Configure when automated actions like notifications and human review
    | requirements should be triggered.
    |
    */

    'auto_human_review_threshold' => 50,
    'auto_notify_threshold' => 51,
    'category_high_score_threshold' => 8,

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notification recipients and settings for high-risk items.
    |
    */

    'notifications' => [
        'enabled' => env('ETHICS_NOTIFICATIONS_ENABLED', true),
        'recipients' => [
            env('ETHICS_NOTIFICATION_EMAIL', 'admin@example.com'),
        ],
        'levels' => ['high', 'critical'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for ethics audits.
    |
    */

    'queue' => [
        'connection' => env('ETHICS_QUEUE_CONNECTION', 'database'),
        'name' => env('ETHICS_QUEUE_NAME', 'default'),
        'retry_attempts' => 3,
        'retry_delay' => 60, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Types
    |--------------------------------------------------------------------------
    |
    | Define the available content types for items.
    |
    */

    'content_types' => [
        'message' => 'Message',
        'ad' => 'Advertisement',
        'script' => 'Script',
        'post' => 'Social Media Post',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Categories
    |--------------------------------------------------------------------------
    |
    | Define the risk categories used in the ethics rubric.
    |
    */

    'risk_categories' => [
        'microtargeting' => 'Microtargeting',
        'emotional_manipulation' => 'Emotional Manipulation',
        'disinformation' => 'Disinformation',
        'voter_suppression' => 'Voter Suppression',
        'vulnerable_populations' => 'Vulnerable Populations',
        'ai_transparency' => 'AI/Transparency',
        'legal_regulatory' => 'Legal/Regulatory',
    ],

];
