<x-filament-panels::page>
    <style>
        /* Remove page header */
        .fi-page-header { display: none !important; }

        /* Make the entire Filament content area fill viewport without scrolling */
        .fi-page { height: 100%; }
        .fi-page > div { height: 100%; display: flex; flex-direction: column; }
        .fi-page > div > .fi-page-content { flex: 1; min-height: 0; padding: 0 !important; }

        /* Prevent body/main scrolling */
        .fi-body-has-navigation .fi-main { height: 100dvh; overflow: hidden; }
        .fi-main-ctn { height: 100%; overflow: hidden; }
    </style>

    <div class="tc-chat-container flex h-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        {{-- Chat Sidebar --}}
        <livewire:team-chat::sidebar :active-type="$activeType" :active-id="$activeId" :wire:key="'sidebar'" />

        {{-- Main content area --}}
        <div class="flex flex-1 flex-col min-w-0">
            @if($activeId)
                {{-- Channel/Conversation Header --}}
                <div class="shrink-0">
                    <livewire:team-chat::channel-header :initial-type="$activeType" :initial-id="$activeId" :wire:key="'header-'.$activeId" />
                </div>

                {{-- Message Feed --}}
                <div class="flex-1 min-h-0 overflow-hidden">
                    <livewire:team-chat::message-feed
                        :initial-type="$activeType"
                        :initial-id="$activeId"
                        :wire:key="'feed-'.$activeId"
                    />
                </div>

                {{-- Message Composer --}}
                <div class="shrink-0">
                    <livewire:team-chat::message-composer :initial-type="$activeType" :initial-id="$activeId" :wire:key="'composer-'.$activeId" />
                </div>
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
                <livewire:team-chat::thread-panel :parent-message-id="$threadParentId" :wire:key="'thread-'.$threadParentId" />
            </div>
        @endif
    </div>

    {{-- Search Modal --}}
    <livewire:team-chat::search-modal :wire:key="'search'" />

    {{-- Member List Modal --}}
    <livewire:team-chat::member-list :wire:key="'members'" />

    {{-- User Profile Card Modal --}}
    <livewire:team-chat::user-profile-card :wire:key="'profile'" />
</x-filament-panels::page>
