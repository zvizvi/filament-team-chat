<x-filament-panels::page>
    <div class="tc-chat-container flex -mx-8 -mt-8 -mb-8 h-[calc(100vh-4rem)] overflow-hidden border-t border-gray-200 dark:border-gray-700">
        {{-- Sidebar --}}
        <livewire:team-chat::sidebar :active-type="$activeType" :active-id="$activeId" />

        {{-- Main content area --}}
        <div class="flex flex-1 flex-col min-w-0">
            @if($activeId)
                {{-- Channel/Conversation Header --}}
                <livewire:team-chat::channel-header />

                {{-- Message Feed --}}
                <livewire:team-chat::message-feed
                    wire:poll.{{ config('team-chat.polling.messages', 3) }}s
                />

                {{-- Message Composer --}}
                <livewire:team-chat::message-composer />
            @else
                <div class="flex flex-1 items-center justify-center text-gray-400 dark:text-gray-500">
                    <div class="text-center">
                        <x-heroicon-o-chat-bubble-left-right class="mx-auto h-12 w-12 mb-4" />
                        <p class="text-lg font-medium">チャンネルまたはDMを選択してください</p>
                        <p class="text-sm mt-1">左のサイドバーからチャンネルを選択するか、DMを開始してください。</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Thread Panel --}}
        @if($showThreadPanel && $threadParentId)
            <div class="w-96 border-l border-gray-200 dark:border-gray-700 flex flex-col bg-white dark:bg-gray-900">
                <livewire:team-chat::thread-panel :parent-message-id="$threadParentId" />
            </div>
        @endif
    </div>

    {{-- Search Modal --}}
    <livewire:team-chat::search-modal />

    {{-- Member List Modal --}}
    <livewire:team-chat::member-list />

    {{-- User Profile Card Modal --}}
    <livewire:team-chat::user-profile-card />
</x-filament-panels::page>
