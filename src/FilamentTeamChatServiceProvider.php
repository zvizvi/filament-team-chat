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
            ->hasTranslations()
            ->hasMigrations([
                '01_create_tc_channels_table',
                '02_create_tc_channel_user_table',
                '03_create_tc_messages_table',
                '04_create_tc_conversations_table',
                '05_create_tc_conversation_user_table',
                '06_create_tc_reactions_table',
                '07_create_tc_read_receipts_table',
                '08_create_tc_mentions_table',
                '09_create_tc_attachments_table',
                '10_create_tc_user_statuses_table',
                '11_add_team_id_to_tc_tables',
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
