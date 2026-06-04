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

        if (! $user || ! method_exists($user, 'unreadChatCount')) {
            return 0;
        }

        return $user->unreadChatCount();
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

    /**
     * Re-render this component (refreshing the floating count) and reload the
     * panel sidebar/topbar so the navigation badge stays in sync.
     */
    public function refreshBadges(): void
    {
        $this->dispatch('refresh-sidebar');
        $this->dispatch('refresh-topbar');
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
