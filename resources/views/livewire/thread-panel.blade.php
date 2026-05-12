<div class="flex h-full flex-col" wire:poll.{{ config('team-chat.polling.messages', 3) }}s>
    {{-- Header --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('team-chat::messages.thread') }}</h3>
        <button wire:click="$dispatch('close-thread')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <x-heroicon-o-x-mark class="h-5 w-5" />
        </button>
    </div>

    {{-- Parent Message --}}
    @if($parentMessage)
        <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
            <div class="flex gap-3">
                <div class="flex-shrink-0">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 text-sm font-semibold">
                        {{ strtoupper(substr($parentMessage->user->name ?? '?', 0, 1)) }}
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline gap-2">
                        <span class="font-semibold text-sm text-gray-900 dark:text-white">
                            {{ $parentMessage->user->name ?? 'Unknown' }}
                        </span>
                        <span class="text-xs text-gray-400">
                            {{ $parentMessage->created_at->format('m/d H:i') }}
                        </span>
                    </div>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                        {!! $parentMessage->body_html !!}
                    </div>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ $this->replies->count() }} {{ Str::plural('reply', $this->replies->count()) }}
            </p>
        </div>
    @endif

    {{-- Replies --}}
    <div class="flex-1 overflow-y-auto p-4 space-y-1">
        @foreach($this->replies as $reply)
            <div class="flex gap-3 rounded-lg px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800/50" wire:key="reply-{{ $reply->id }}">
                <div class="flex-shrink-0 pt-0.5">
                    <div class="flex h-7 w-7 items-center justify-center rounded-md bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 text-xs font-semibold">
                        {{ strtoupper(substr($reply->user->name ?? '?', 0, 1)) }}
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline gap-2">
                        <span class="font-semibold text-sm text-gray-900 dark:text-white">
                            {{ $reply->user->name ?? 'Unknown' }}
                        </span>
                        <span class="text-xs text-gray-400">
                            {{ $reply->created_at->format('H:i') }}
                        </span>
                    </div>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                        {!! $reply->body_html !!}
                    </div>
                </div>
            </div>
        @endforeach

        @if($this->replies->isEmpty())
            <p class="text-center text-sm text-gray-400 dark:text-gray-500 py-4">
                {{ __('team-chat::messages.no_replies') }}
            </p>
        @endif
    </div>

    {{-- Reply Composer --}}
    <div class="border-t border-gray-200 dark:border-gray-700 p-4">
        <form wire:submit="sendReply" class="flex gap-2">
            <input
                type="text"
                wire:model="replyBody"
                placeholder="{{ __('team-chat::messages.type_reply') }}"
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                autocomplete="off"
            />
            <button
                type="submit"
                class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <x-heroicon-o-paper-airplane class="h-4 w-4" />
            </button>
        </form>
    </div>
</div>
