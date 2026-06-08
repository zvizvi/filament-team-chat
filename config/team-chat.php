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
    | Navigation Icon
    |--------------------------------------------------------------------------
    |
    | Icon for the Team Chat navigation item. `navigation_active_icon` is used
    | only when the item is active; leave it null to reuse `navigation_icon`.
    |
    */
    'navigation_icon' => 'heroicon-o-chat-bubble-left-right',
    'navigation_active_icon' => null,

    /*
    |--------------------------------------------------------------------------
    | Channel Manager Takes Ownership
    |--------------------------------------------------------------------------
    |
    | Channel management is owner-based. When this is true, an app-level manager
    | (see channel_manager_method) who joins a channel takes ownership of it, and
    | the previous non-manager owner is demoted to member. Opt-in; default false.
    |
    */
    'channel_manager_takes_ownership' => false,

    /*
    |--------------------------------------------------------------------------
    | Channel Manager Method
    |--------------------------------------------------------------------------
    |
    | Optional boolean method on the user model (e.g. 'isAdmin') that identifies
    | the app-level managers used by the ownership takeover above. Leave null to
    | disable the takeover.
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
    | Presence (in seconds)
    |--------------------------------------------------------------------------
    |
    | A user is shown as online while their last_seen_at is within `timeout`
    | seconds. While the chat is open, last_seen_at is refreshed every
    | `heartbeat` seconds (keep heartbeat well below timeout).
    |
    */
    'presence' => [
        'heartbeat' => 30,
        'timeout' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Floating Unread Button
    |--------------------------------------------------------------------------
    |
    | Show a floating chat button (bottom-left) with the total unread count on
    | every panel page when the user has unread messages. Set to false to hide.
    |
    */
    'floating_button' => true,

    /*
    |--------------------------------------------------------------------------
    | Navigation Badge
    |--------------------------------------------------------------------------
    |
    | Show the total unread count as a badge on the Team Chat navigation item.
    | Set to false to hide.
    |
    */
    'navigation_badge' => true,

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
