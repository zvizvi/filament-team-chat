<div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-4 py-3">
    @if($headerName)
        <div class="min-w-0">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                @if($headerType === 'channel')
                    <span class="text-gray-400 mr-1">#</span>
                @else
                    <x-heroicon-o-chat-bubble-oval-left class="inline-block h-5 w-5 text-gray-400 mr-1" />
                @endif
                {{ $headerName }}
            </h2>
            @if($headerDescription)
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $headerDescription }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="showMembers" class="flex items-center gap-1 text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" title="メンバー一覧">
                <x-heroicon-o-users class="h-4 w-4" />
                {{ $memberCount }}
            </button>
            @if($headerType === 'channel' && $isOwner)
                <button
                    wire:click="archiveChannel"
                    wire:confirm="このチャンネルをアーカイブしますか？"
                    class="text-gray-400 hover:text-yellow-500 transition-colors"
                    title="チャンネルをアーカイブ"
                >
                    <x-heroicon-o-archive-box class="h-4 w-4" />
                </button>
            @endif
        </div>
    @endif
</div>
