<?php

namespace Filament\TeamChat;

use Filament\TeamChat\Livewire\ChannelHeader;
use Filament\TeamChat\Livewire\MemberList;
use Filament\TeamChat\Livewire\MessageComposer;
use Filament\TeamChat\Livewire\MessageFeed;
use Filament\TeamChat\Livewire\SearchModal;
use Filament\TeamChat\Livewire\Sidebar;
use Filament\TeamChat\Livewire\ThreadPanel;
use Filament\TeamChat\Livewire\UserProfileCard;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentTeamChatServiceProvider extends PackageServiceProvider
{
    public static string $name = 'team-chat';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                'create_tc_channels_table',
                'create_tc_channel_user_table',
                'create_tc_messages_table',
                'create_tc_conversations_table',
                'create_tc_conversation_user_table',
                'create_tc_reactions_table',
                'create_tc_read_receipts_table',
                'create_tc_mentions_table',
                'create_tc_attachments_table',
                'create_tc_user_statuses_table',
            ]);
    }

    public function packageBooted(): void
    {
        Livewire::component('team-chat::sidebar', Sidebar::class);
        Livewire::component('team-chat::message-feed', MessageFeed::class);
        Livewire::component('team-chat::message-composer', MessageComposer::class);
        Livewire::component('team-chat::channel-header', ChannelHeader::class);
        Livewire::component('team-chat::thread-panel', ThreadPanel::class);
        Livewire::component('team-chat::search-modal', SearchModal::class);
        Livewire::component('team-chat::member-list', MemberList::class);
        Livewire::component('team-chat::user-profile-card', UserProfileCard::class);
    }
}
