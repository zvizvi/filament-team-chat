<div wire:poll.15s>
    @if($this->url && ! $this->onChatPage && $this->unreadCount > 0)
        <a
            href="{{ $this->url }}"
            wire:navigate
            aria-label="{{ __('team-chat::messages.navigation_label') }}"
            class="fixed bottom-4 left-4 z-50 inline-flex h-14 w-14 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg transition-colors hover:bg-primary-700"
        >
            <x-heroicon-o-chat-bubble-left-right class="h-6 w-6" />
            <span class="absolute -top-1 -end-1 inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white ring-2 ring-white dark:ring-gray-900">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        </a>
    @endif
</div>
