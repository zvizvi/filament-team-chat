<div class="flex h-full w-64 flex-col bg-gray-50 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700"
     wire:poll.{{ config('team-chat.polling.sidebar', 5) }}s>
    {{-- Workspace Header --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white truncate">Team Chat</h2>
        <button wire:click="$dispatch('open-search')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="検索">
            <x-heroicon-o-magnifying-glass class="h-5 w-5" />
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-2">
        {{-- Channels Section --}}
        <div class="flex items-center justify-between px-4 py-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                チャンネル
            </span>
            <div class="flex items-center gap-1">
                <button wire:click="toggleBrowseChannels" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="チャンネルを閲覧">
                    <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                </button>
                <button wire:click="toggleCreateChannel" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="チャンネルを作成">
                    <x-heroicon-o-plus class="h-4 w-4" />
                </button>
            </div>
        </div>

        {{-- Create Channel Form --}}
        @if($showCreateChannel)
            <form wire:submit="createChannel" class="px-4 py-2">
                <input
                    type="text"
                    wire:model="newChannelName"
                    placeholder="チャンネル名"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    autofocus
                />
                @error('newChannelName')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                <div class="mt-2 flex gap-2">
                    <button type="submit" class="rounded-md bg-primary-600 px-3 py-1 text-xs font-medium text-white hover:bg-primary-700">
                        作成
                    </button>
                    <button type="button" wire:click="toggleCreateChannel" class="rounded-md px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                        キャンセル
                    </button>
                </div>
            </form>
        @endif

        {{-- Channel List --}}
        <nav class="mt-1 space-y-0.5 px-2">
            @foreach($this->channels as $channel)
                @php $unread = $channel->unreadCountFor(auth()->id()); @endphp
                <button
                    wire:click="selectChannel({{ $channel->id }})"
                    @class([
                        'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors',
                        'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' => $activeType === 'channel' && $activeId === $channel->id,
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => ! ($activeType === 'channel' && $activeId === $channel->id),
                        'font-semibold' => $unread > 0,
                    ])
                >
                    <span class="text-gray-400">#</span>
                    <span class="truncate flex-1 text-left">{{ $channel->name }}</span>
                    @if($unread > 0)
                        <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-600 px-1.5 text-xs font-medium text-white">
                            {{ $unread > 99 ? '99+' : $unread }}
                        </span>
                    @endif
                </button>
            @endforeach
        </nav>

        @if($this->channels->isEmpty())
            <p class="px-4 py-2 text-sm text-gray-400 dark:text-gray-500">
                チャンネルがありません
            </p>
        @endif

        {{-- Browse Channels --}}
        @if($showBrowseChannels)
            <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">参加可能なチャンネル</p>
                @forelse($this->browsableChannels as $channel)
                    <div class="flex items-center justify-between py-1">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="text-gray-400">#</span> {{ $channel->name }}
                        </span>
                        <button
                            wire:click="joinChannel({{ $channel->id }})"
                            class="text-xs text-primary-600 hover:text-primary-700 font-medium"
                        >
                            参加
                        </button>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">参加可能なチャンネルはありません</p>
                @endforelse
            </div>
        @endif

        {{-- DM Section --}}
        <div class="mt-4 flex items-center justify-between px-4 py-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                ダイレクトメッセージ
            </span>
            <button wire:click="toggleStartDm" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-heroicon-o-plus class="h-4 w-4" />
            </button>
        </div>

        {{-- Start DM Form --}}
        @if($showStartDm)
            <form wire:submit="startDirectMessage" class="px-4 py-2">
                <select
                    wire:model="dmUserId"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">ユーザーを選択...</option>
                    @foreach($this->availableUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('dmUserId')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                <div class="mt-2 flex gap-2">
                    <button type="submit" class="rounded-md bg-primary-600 px-3 py-1 text-xs font-medium text-white hover:bg-primary-700">
                        開始
                    </button>
                    <button type="button" wire:click="toggleStartDm" class="rounded-md px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                        キャンセル
                    </button>
                </div>
            </form>
        @endif

        {{-- Conversation List --}}
        <nav class="mt-1 space-y-0.5 px-2">
            @foreach($this->conversations as $conversation)
                @php $unread = $conversation->unreadCountFor(auth()->id()); @endphp
                <button
                    wire:click="selectConversation({{ $conversation->id }})"
                    @class([
                        'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors',
                        'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' => $activeType === 'conversation' && $activeId === $conversation->id,
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => ! ($activeType === 'conversation' && $activeId === $conversation->id),
                        'font-semibold' => $unread > 0,
                    ])
                >
                    <x-heroicon-o-chat-bubble-oval-left class="h-4 w-4 flex-shrink-0 text-gray-400" />
                    <span class="truncate flex-1 text-left">{{ $conversation->getDisplayNameForUser(auth()->user()) }}</span>
                    @if($unread > 0)
                        <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-600 px-1.5 text-xs font-medium text-white">
                            {{ $unread > 99 ? '99+' : $unread }}
                        </span>
                    @endif
                </button>
            @endforeach
        </nav>

        @if($this->conversations->isEmpty() && ! $showStartDm)
            <p class="px-4 py-2 text-sm text-gray-400 dark:text-gray-500">
                DMがありません
            </p>
        @endif
    </div>
</div>
