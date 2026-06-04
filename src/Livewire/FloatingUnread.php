<?php

namespace Filament\TeamChat\Livewire;

use Filament\TeamChat\Pages\TeamChat;
use Livewire\Component;

class FloatingUnread extends Component
{
    /**
     * Total unread messages for the current user across their channels and DMs.
     */
    public function getUnreadCountProperty(): int
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'channels')) {
            return 0;
        }

        $key = $user->getAuthIdentifier();
        $total = 0;

        foreach ($user->channels()->whereNull('archived_at')->get() as $channel) {
            $total += $channel->unreadCountFor($key);
        }

        foreach ($user->conversations()->get() as $conversation) {
            $total += $conversation->unreadCountFor($key);
        }

        return $total;
    }

    /**
     * URL of the Team Chat page, or null when it is not available on this panel.
     */
    public function getUrlProperty(): ?string
    {
        try {
            return TeamChat::getUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    public function getOnChatPageProperty(): bool
    {
        try {
            return request()->routeIs(TeamChat::getRouteName());
        } catch (\Throwable) {
            return false;
        }
    }

    public function render()
    {
        return view('team-chat::livewire.floating-unread');
    }
}
