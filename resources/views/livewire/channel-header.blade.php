<div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-4 py-3">
    @if($headerName && ! $isEditing)
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
                <button wire:click="startEditing" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" title="チャンネル設定">
                    <x-heroicon-o-cog-6-tooth class="h-4 w-4" />
                </button>
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
    @elseif($isEditing)
        <form wire:submit="saveChannel" class="flex flex-1 items-center gap-3">
            <div class="flex-1 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">#</span>
                    <input
                        type="text"
                        wire:model="editName"
                        placeholder="チャンネル名"
                        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    />
                    <select
                        wire:model="editType"
                        class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="public">パブリック</option>
                        <option value="private">プライベート</option>
                    </select>
                </div>
                <input
                    type="text"
                    wire:model="editTopic"
                    placeholder="トピック（任意）"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                />
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <button type="submit" class="rounded-md bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700">
                    保存
                </button>
                <button type="button" wire:click="cancelEditing" class="rounded-md px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    キャンセル
                </button>
            </div>
        </form>
    @endif
</div>
