<?php

namespace Filament\TeamChat;

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
                'add_team_id_to_tc_tables',
            ]);
    }

    public function packageBooted(): void
    {
        Livewire::addNamespace(
            namespace: 'team-chat',
            classNamespace: 'Filament\\TeamChat\\Livewire',
            classPath: __DIR__.'/Livewire',
        );
    }
}
