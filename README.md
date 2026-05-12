# Filament Team Chat

A Slack-like team chat plugin for Filament v5. Add real-time messaging, channels, direct messages, threads, reactions, file sharing, and more to any Filament panel.

![Filament v5](https://img.shields.io/badge/Filament-v5-amber?style=flat-square)
![Laravel v13](https://img.shields.io/badge/Laravel-v13-red?style=flat-square)
![PHP 8.3+](https://img.shields.io/badge/PHP-8.3+-blue?style=flat-square)
![License MIT](https://img.shields.io/badge/License-MIT-green?style=flat-square)

## Features

- **Channels** - Public and private channels with member management
- **Public Channel Auto-Join** - Public channels visible to all users, auto-join on first click
- **Direct Messages** - 1-on-1 and group DMs
- **Threads** - Reply to any message in a threaded conversation via hover action or reply link
- **Reactions** - Emoji reactions on messages (8 built-in emojis)
- **Mentions** - @user, @channel, and @here with autocomplete
- **File Attachments** - Upload and preview images, documents, and more
- **Message Edit/Delete** - Edit and soft-delete your own messages
- **Unread Management** - Unread counts, badges, and automatic read tracking
- **Instant Refresh** - Sent messages appear immediately (no polling delay for sender)
- **Search** - Full-text message search with access control
- **Online Status** - Real-time presence indicators and user profiles
- **Notifications** - Database notifications for mentions and DMs
- **Channel Admin** - Filament resource for channel CRUD management
- **Real-time Updates** - Livewire polling (configurable intervals) with instant refresh on send
- **Multi-Tenancy Ready** - Optional `team_id` scoping for multi-tenant applications
- **Dark Mode** - Full dark mode support via Tailwind CSS

## Requirements

- PHP 8.3+
- Laravel 13+
- Filament 5+
- Livewire 4+

## Installation

Install the package via Composer:

```bash
composer require qalainau/filament-team-chat
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag=team-chat-migrations
php artisan migrate
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=team-chat-config
```

## Getting Started

### 1. Register the Plugin

Add the plugin to your Filament panel provider:

```php
use Filament\TeamChat\FilamentTeamChatPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentTeamChatPlugin::make());
}
```

### 2. Add the Trait to Your User Model

Add the `HasTeamChat` trait to your User model:

```php
use Filament\TeamChat\Concerns\HasTeamChat;

class User extends Authenticatable
{
    use HasTeamChat;

    // ...
}
```

### 3. Create the Notifications Table (if needed)

If your application does not already have a `notifications` table (required for database notifications on mentions and DMs):

```bash
php artisan make:notifications-table
php artisan migrate
```

### 4. Add Tailwind Source (Custom Theme)

If you use a custom Filament theme, add the package views to your theme's CSS so Tailwind classes are compiled:

```css
/* resources/css/filament/admin/theme.css */
@source '../../../../vendor/filament/team-chat/resources/views/**/*';
```

Then rebuild: `npm run build`

That's it! Visit `/admin/team-chat` to start chatting.

## Configuration

The config file `config/team-chat.php` allows you to customize:

```php
return [
    // Table prefix to avoid collisions
    'table_prefix' => 'tc_',

    // User model class
    'user_model' => \App\Models\User::class,

    // Polling intervals (seconds)
    'polling' => [
        'messages' => 3,
        'sidebar' => 5,
    ],

    // File upload settings
    'uploads' => [
        'disk' => 'public',
        'directory' => 'team-chat-attachments',
        'max_size' => 10240, // KB
    ],

    // Multi-tenancy (optional)
    'tenancy' => [
        'enabled' => false,
        'model' => null, // e.g. \App\Models\Team::class
        'resolver' => null, // callable or class that returns current tenant ID
    ],
];
```

### Polling Intervals

Adjust polling intervals to balance between responsiveness and server load:

```php
'polling' => [
    'messages' => 3,  // Message feed refresh (seconds)
    'sidebar' => 5,   // Sidebar unread counts refresh (seconds)
],
```

### Multi-Tenancy

Enable multi-tenancy to scope channels and conversations per team:

```php
'tenancy' => [
    'enabled' => true,
    'model' => \App\Models\Team::class,
    'resolver' => null, // null = auto-detect from Filament tenant
],
```

When enabled, all channels and conversations are automatically scoped to the current tenant. The resolver supports three modes:

1. **null** (default) - Auto-detects from `Filament::getTenant()`
2. **Callable** - `'resolver' => fn () => auth()->user()->current_team_id`
3. **Class** - `'resolver' => \App\Services\TenantResolver::class` (must have a `resolve()` method)

## Architecture

### Database Tables

All tables use a `tc_` prefix to avoid collisions with your application:

| Table | Purpose |
|---|---|
| `tc_channels` | Chat channels (public/private), with optional `team_id` |
| `tc_channel_user` | Channel membership pivot |
| `tc_conversations` | Direct messages (1-on-1 and group), with optional `team_id` |
| `tc_conversation_user` | DM participant pivot |
| `tc_messages` | Messages (polymorphic: Channel or Conversation) |
| `tc_reactions` | Emoji reactions on messages |
| `tc_attachments` | File attachments on messages |
| `tc_mentions` | @user, @channel, @here mentions |
| `tc_read_receipts` | Per-user read tracking (polymorphic) |
| `tc_user_statuses` | Online status and user profiles |

### Models

```
Channel         - Public/private channels with members (BelongsToTeam, HasReadReceipts)
Conversation    - 1-on-1 and group direct messages (BelongsToTeam, HasReadReceipts)
Message         - Polymorphic messages with Markdown support
Reaction        - Emoji reactions (toggle behavior)
Attachment      - File uploads with image detection
Mention         - Parsed @mentions from message body
ReadReceipt     - Last-read message tracking per user
UserStatus      - Online presence and custom status
```

### Livewire Components

The chat UI is composed of 8 Livewire components, registered via `Livewire::addNamespace()`:

| Component | Purpose | Polling |
|---|---|---|
| `Sidebar` | Channel/DM list, unread badges, create/join, browse public channels | 5s |
| `MessageFeed` | Message list, reactions, edit/delete, reply button | 3s |
| `MessageComposer` | Text input, file upload, mention autocomplete | - |
| `ChannelHeader` | Channel name, topic, member count, archive | - |
| `ThreadPanel` | Thread replies with own composer | 3s |
| `SearchModal` | Full-text search across channels/DMs | - |
| `MemberList` | Channel/DM member list with online status | - |
| `UserProfileCard` | User profile popup with DM action | - |

Components also respond to events for instant refresh (e.g. `message-sent` triggers immediate re-render of MessageFeed and Sidebar).

### Actions

Business logic is encapsulated in action classes:

| Action | Purpose |
|---|---|
| `SendMessage` | Create message, parse mentions, store attachments, notify |
| `ToggleReaction` | Add/remove emoji reaction (toggle) |
| `MarkAsRead` | Update read receipt for channel/conversation |
| `ParseMentions` | Extract @mentions and create styled HTML |
| `SearchMessages` | Search messages with access control |

## Usage

### Channels

Create a channel programmatically:

```php
use Filament\TeamChat\Models\Channel;

$channel = Channel::create([
    'name' => 'general',
    'slug' => 'general',
    'type' => 'public', // or 'private'
    'created_by' => $user->id,
]);

$channel->members()->attach($user->id, ['role' => 'owner']);
```

> Public channels are visible to all users in the sidebar. When a user clicks a public channel they haven't joined, they are automatically added as a member.

### Direct Messages

Start a DM or find an existing one:

```php
// 1-on-1 DM (idempotent - returns existing if found)
$conversation = $user->findOrCreateDirectMessage($otherUserId);

// Group DM
$conversation = $user->createGroupConversation(
    userIds: [$user2->id, $user3->id],
    name: 'Project Team',
);
```

### Sending Messages

```php
use Filament\TeamChat\Actions\SendMessage;

$message = app(SendMessage::class)->execute(
    messageable: $channel,    // Channel or Conversation
    userId: $user->id,
    body: 'Hello @Bob! Check this **bold** text.',
    parentId: null,           // Set for thread replies
    files: [],                // Array of UploadedFile
);
```

### Reactions

```php
use Filament\TeamChat\Actions\ToggleReaction;

// Returns true if added, false if removed
$added = app(ToggleReaction::class)->execute(
    messageId: $message->id,
    userId: $user->id,
    emoji: '👍',
);
```

### Unread Management

```php
use Filament\TeamChat\Actions\MarkAsRead;

// Get unread count
$count = $channel->unreadCountFor($user->id);

// Mark as read
app(MarkAsRead::class)->execute($channel, $user->id);
```

### Search

```php
use Filament\TeamChat\Actions\SearchMessages;

// Only searches channels/DMs the user belongs to
$results = app(SearchMessages::class)->execute(
    userId: $user->id,
    query: 'search term',
    limit: 20,
);
```

### Online Status

```php
// Mark user as online
$user->touchOnline();

// Get or create status
$status = $user->getOrCreateStatus();
$status->update([
    'display_name' => 'Cool Name',
    'status_emoji' => '🏖️',
    'status_text' => 'On vacation',
]);
```

## Filament Integration

### Chat Page

The plugin registers a custom Filament page at `/admin/team-chat` with a full-viewport Slack-like layout. The page uses Alpine.js to dynamically calculate the available height, ensuring the chat fills the viewport without page scrolling. Filament's authentication middleware is preserved.

### Channel Resource

An admin resource at `/admin/channels` provides CRUD management for channels with:

- Searchable/sortable channel list
- Channel type filter (public/private)
- Member count column
- Create with automatic owner assignment
- Edit channel details
- Delete channels

### Navigation

Both the Team Chat page and Channel Resource appear in the Filament navigation:

| Item | Icon | Group |
|---|---|---|
| Team Chat | `heroicon-o-chat-bubble-left-right` | - |
| Channel Management | `heroicon-o-hashtag` | Team Chat |

## Testing

The package includes a comprehensive test suite (107 tests). Run tests in your application:

```bash
php artisan test --filter=TeamChat
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
