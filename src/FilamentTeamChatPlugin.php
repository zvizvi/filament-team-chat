<?php

namespace Filament\TeamChat;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\TeamChat\Pages\TeamChat;

class FilamentTeamChatPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'team-chat';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            TeamChat::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
