<?php

use App\Models\User;

return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | All database tables created by this plugin will use this prefix
    | to avoid collisions with the host application's tables.
    |
    */
    'table_prefix' => 'tc_',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of the user model.
    |
    */
    'user_model' => User::class,

    /*
    |--------------------------------------------------------------------------
    | Polling Intervals (in seconds)
    |--------------------------------------------------------------------------
    */
    'polling' => [
        'messages' => 3,
        'sidebar' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'disk' => 'public',
        'directory' => 'team-chat-attachments',
        'max_size' => 10240, // KB
    ],
];
