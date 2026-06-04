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
    | User Scope
    |--------------------------------------------------------------------------
    |
    | Optional Eloquent local scope (by name) applied to the user model when
    | listing mentionable users, choosing DM recipients, and parsing @mentions.
    | For example, 'active' calls User::scopeActive() so only active users are
    | shown. Leave null to include all users.
    |
    */
    'user_scope' => null,

    /*
    |--------------------------------------------------------------------------
    | Navigation Sort
    |--------------------------------------------------------------------------
    |
    | Sort order for the Team Chat page within the Filament navigation. Leave
    | null to use the default.
    |
    */
    'navigation_sort' => null,

    /*
    |--------------------------------------------------------------------------
    | Channel Manager
    |--------------------------------------------------------------------------
    |
    | Optional boolean method on the user model (e.g. 'isAdmin'). When a user
    | for whom it returns true joins a channel, they become its owner and any
    | previous non-matching owner is demoted to member. Channel management is
    | owner-based. Leave null to disable this takeover.
    |
    */
    'channel_manager_method' => null,

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

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | Enable multi-tenancy to scope channels and conversations per team.
    | When enabled, set the tenant model and the method to resolve the
    | current tenant ID (e.g. from Filament's tenant or auth).
    |
    */
    'tenancy' => [
        'enabled' => false,
        'model' => null, // e.g. \App\Models\Team::class
        'resolver' => null, // callable or class that returns current tenant ID
    ],
];
